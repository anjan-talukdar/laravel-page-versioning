<?php

namespace AnjanTalukdar\PageVersioning\Filament\Resources\PageResource\RelationManagers;

use AnjanTalukdar\PageVersioning\Enums\PageVersionStatus;
use AnjanTalukdar\PageVersioning\Models\Page;
use AnjanTalukdar\PageVersioning\Models\PageVersion;
use AnjanTalukdar\PageVersioning\Services\PageService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class PageVersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'versions';

    protected static ?string $title = 'Version History & Revisions';

    protected static ?string $recordTitleAttribute = 'version_code';

    public function form(Schema $schema): Schema
    {
        /** @var Page $page */
        $page = $this->getOwnerRecord();
        /** @var PageService $pageService */
        $pageService = app(PageService::class);
        $nextVersionCode = $page ? $pageService->generateNextVersionCode($page) : 'v1.0.0';

        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Page Title')
                    ->default(fn() => $page?->currentVersion?->title ?? $page?->slug)
                    ->required(),
                TextInput::make('version_name')
                    ->label('Version Name')
                    ->placeholder('e.g., DPDP Compliance Update')
                    ->required(),
                TextInput::make('version_code')
                    ->label('Version Code')
                    ->default($nextVersionCode)
                    ->required(),
                Select::make('status')
                    ->options([
                        PageVersionStatus::DRAFT->value => PageVersionStatus::DRAFT->label(),
                        PageVersionStatus::PUBLISHED->value => PageVersionStatus::PUBLISHED->label(),
                    ])
                    ->default(PageVersionStatus::DRAFT->value)
                    ->required(),
                RichEditor::make('content')
                    ->label('Page Content')
                    ->default(fn() => $page?->currentVersion?->content)
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('change_summary')
                    ->label('Change Summary')
                    ->placeholder('Describe what was changed in this version...')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('version_code')
                    ->label('Version Code')
                    ->badge()
                    ->color(fn(PageVersion $record) => $record->isCurrent() ? 'success' : 'gray')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('version_name')
                    ->label('Version Name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->limit(30),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn(PageVersionStatus $state) => $state->label())
                    ->color(fn(PageVersionStatus $state) => $state->color())
                    ->icon(fn(PageVersionStatus $state) => $state->icon()),
                TextColumn::make('published_at')
                    ->label('Published Date')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
                TextColumn::make('creator.name')
                    ->label('Created By')
                    ->default('System')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Created Date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label('New Version / Revision')
                    ->using(function (array $data): Model {
                        /** @var Page $page */
                        $page = $this->getOwnerRecord();
                        /** @var PageService $pageService */
                        $pageService = app(PageService::class);

                        $status = PageVersionStatus::from($data['status']);
                        return $pageService->createVersion($page, $data, $status, Auth::id());
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->schema([
                        TextInput::make('version_name')->disabled(),
                        TextInput::make('version_code')->disabled(),
                        TextInput::make('title')->disabled(),
                        Textarea::make('change_summary')->disabled(),
                        RichEditor::make('content')->disabled()->columnSpanFull(),
                    ]),

                Action::make('publish')
                    ->label('Publish')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->hidden(fn(PageVersion $record) => $record->isPublished() && $record->isCurrent())
                    ->requiresConfirmation()
                    ->action(function (PageVersion $record) {
                        /** @var Page $page */
                        $page = $this->getOwnerRecord();
                        /** @var PageService $pageService */
                        $pageService = app(PageService::class);

                        $pageService->publishVersion($page, $record);

                        Notification::make()
                            ->title('Version Published')
                            ->body("Version {$record->version_code} is now active and published.")
                            ->success()
                            ->send();
                    }),

                Action::make('rollback')
                    ->label('Rollback to This')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->hidden(fn(PageVersion $record) => $record->isCurrent())
                    ->schema([
                        TextInput::make('custom_version_name')
                            ->label('New Version Name for Rollback')
                            ->default(fn(PageVersion $record) => "Rollback to " . ($record->version_name ?: $record->version_code))
                            ->required(),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Rollback Page Revision')
                    ->modalDescription('Historical versions are never modified. Rolling back will duplicate this revision as a brand-new active version.')
                    ->action(function (PageVersion $record, array $data) {
                        /** @var Page $page */
                        $page = $this->getOwnerRecord();
                        /** @var PageService $pageService */
                        $pageService = app(PageService::class);

                        $newVersion = $pageService->rollbackToVersion(
                            $page,
                            $record,
                            $data['custom_version_name'] ?? null,
                            Auth::id()
                        );

                        Notification::make()
                            ->title('Rollback Complete')
                            ->body("Created and published version {$newVersion->version_code} based on revision {$record->version_code}.")
                            ->success()
                            ->send();
                    }),

                EditAction::make()
                    ->hidden(fn(PageVersion $record) => $record->isPublished() || $record->isArchived()),

                DeleteAction::make()
                    ->hidden(fn(PageVersion $record) => $record->isCurrent())
            ]);
    }
}
