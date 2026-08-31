<?php

namespace AnjanTalukdar\PageVersioning\Filament\Resources;

use AnjanTalukdar\PageVersioning\Filament\PageVersioningPlugin;
use AnjanTalukdar\PageVersioning\Filament\Resources\PageResource\Pages\CreatePageResource;
use AnjanTalukdar\PageVersioning\Filament\Resources\PageResource\Pages\EditPageResource;
use AnjanTalukdar\PageVersioning\Filament\Resources\PageResource\Pages\ListPageResource;
use AnjanTalukdar\PageVersioning\Filament\Resources\PageResource\RelationManagers\PageVersionsRelationManager;
use AnjanTalukdar\PageVersioning\Models\Page;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\BulkActionGroup;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class PageResource extends Resource
{
    public static function getModel(): string
    {
        return config('page-versioning.models.page', Page::class);
    }

    public static function getNavigationGroup(): ?string
    {
        if (class_exists(PageVersioningPlugin::class) && filament()->hasPlugin('laravel-page-versioning')) {
            /** @var PageVersioningPlugin $plugin */
            $plugin = filament('laravel-page-versioning');
            return $plugin->getNavigationGroup();
        }

        return config('page-versioning.filament.navigation_group', 'Content Management');
    }

    public static function getNavigationIcon(): ?string
    {
        if (class_exists(PageVersioningPlugin::class) && filament()->hasPlugin('laravel-page-versioning')) {
            /** @var PageVersioningPlugin $plugin */
            $plugin = filament('laravel-page-versioning');
            return $plugin->getNavigationIcon();
        }

        return config('page-versioning.filament.navigation_icon', 'heroicon-o-document-duplicate');
    }

    public static function getNavigationSort(): ?int
    {
        if (class_exists(PageVersioningPlugin::class) && filament()->hasPlugin('laravel-page-versioning')) {
            /** @var PageVersioningPlugin $plugin */
            $plugin = filament('laravel-page-versioning');
            return $plugin->getNavigationSort();
        }

        return config('page-versioning.filament.navigation_sort', 10);
    }

    public static function form(Schema $schema): Schema
    {
        $types = config('page-versioning.default_types', [
            'legal' => 'Legal & Policies',
            'general' => 'General Information',
        ]);

        return $schema
            ->components([
                Section::make('Page Identity')
                    ->description('Set category type and unique URL slug for this page.')
                    ->schema([
                        Select::make('type')
                            ->label('Page Category / Type')
                            ->options($types)
                            ->default('general')
                            ->required()
                            ->searchable(),
                        TextInput::make('slug')
                            ->label('URL Slug')
                            ->placeholder('e.g., privacy-policy')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn($set, ?string $state) => $set('slug', Str::slug($state))),
                    ])->columns(2)
                    ->columnSpanFull(),

                Section::make('Initial Version Setup')
                    ->description('Create the first published or draft revision for this page.')
                    ->hidden(fn(?Page $record) => $record !== null) // Only show on Create page
                    ->schema([
                        TextInput::make('initial_title')
                            ->label('Page Title')
                            ->placeholder('e.g., Privacy Policy')
                            ->required(fn(?Page $record) => $record === null)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($get, $set, ?string $state) {
                                if (!$get('slug')) {
                                    $set('slug', Str::slug($state));
                                }
                            }),
                        TextInput::make('initial_version_name')
                            ->label('Version Name')
                            ->default('v1.0.0')
                            ->placeholder('e.g., v1.0.0 or Initial Release')
                            ->required(fn(?Page $record) => $record === null)
                            ->columnSpan(2),
                        RichEditor::make('initial_content')
                            ->label('Page Content')
                            ->required(fn(?Page $record) => $record === null)
                            ->extraInputAttributes(['style' => 'min-height: 400px; max-height: 400px; overflow-y: auto;'])
                            ->columnSpanFull(),
                        Textarea::make('initial_change_summary')
                            ->label('Change Summary')
                            ->default('Initial version creation')
                            ->rows(2)
                            ->columnSpanFull(),
                        Toggle::make('initial_publish')
                            ->label('Publish Immediately')
                            ->default(true),
                    ])->columns(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        $types = config('page-versioning.default_types', [
            'legal' => 'Legal & Policies',
            'general' => 'General Information',
        ]);

        return $table
            ->columns([
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => $types[$state] ?? ucfirst($state))
                    ->color('info')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('slug')
                    ->fontFamily('mono')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('currentVersion.title')
                    ->label('Current Title')
                    ->default('No version published')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('currentVersion.version_name')
                    ->label('Version Name')
                    ->default('-')
                    ->sortable(),
                TextColumn::make('currentVersion.version_code')
                    ->label('Revision #')
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn($state) => $state ? "#{$state}" : 'N/A')
                    ->sortable(),
                TextColumn::make('currentVersion.published_at')
                    ->label('Published Date')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options($types),
                TrashedFilter::make(),
            ])
            ->actions([
                Action::make('visit')
                    ->label('View Page')
                    ->icon(Heroicon::ArrowTopRightOnSquare)
                    ->color('info')
                    ->url(fn(Page $record): string => route('page-versioning.show', $record->slug))
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PageVersionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPageResource::route('/'),
            'create' => CreatePageResource::route('/create'),
            'edit' => EditPageResource::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
