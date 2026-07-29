<?php

namespace AnjanTalukdar\PageVersioning\Filament\Resources;

use AnjanTalukdar\PageVersioning\Filament\PageVersioningPlugin;
use AnjanTalukdar\PageVersioning\Filament\Resources\PageResource\RelationManagers\PageVersionsRelationManager;
use AnjanTalukdar\PageVersioning\Models\Page;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
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
        if (class_exists(Filament::class) && Filament::getCurrentPanel()) {
            try {
                $plugin = Filament::getCurrentPanel()->getPlugin('laravel-page-versioning');
                if ($plugin instanceof PageVersioningPlugin) {
                    return $plugin->getNavigationGroup();
                }
            } catch (\Throwable $e) {
                // Fallback to config
            }
        }
        return config('page-versioning.filament.navigation_group', 'Content Management');
    }

    public static function getNavigationIcon(): ?string
    {
        if (class_exists(Filament::class) && Filament::getCurrentPanel()) {
            try {
                $plugin = Filament::getCurrentPanel()->getPlugin('laravel-page-versioning');
                if ($plugin instanceof PageVersioningPlugin) {
                    return $plugin->getNavigationIcon();
                }
            } catch (\Throwable $e) {
                // Fallback to config
            }
        }
        return config('page-versioning.filament.navigation_icon', 'heroicon-o-document-duplicate');
    }

    public static function getNavigationSort(): ?int
    {
        if (class_exists(Filament::class) && Filament::getCurrentPanel()) {
            try {
                $plugin = Filament::getCurrentPanel()->getPlugin('laravel-page-versioning');
                if ($plugin instanceof PageVersioningPlugin) {
                    return $plugin->getNavigationSort();
                }
            } catch (\Throwable $e) {
                // Fallback to config
            }
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
                Section::make('Page Settings')
                    ->description('Basic configuration and URL slug for this static page.')
                    ->schema([
                        Select::make('type')
                            ->options($types)
                            ->default('general')
                            ->required(),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Unique URL identifier (e.g., privacy-policy, terms-of-service).')
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
                            ->default('Initial Release')
                            ->placeholder('e.g., Initial Release')
                            ->required(fn(?Page $record) => $record === null),
                        TextInput::make('initial_version_code')
                            ->label('Version Code')
                            ->default('v1.0.0')
                            ->placeholder('e.g., v1.0.0')
                            ->required(fn(?Page $record) => $record === null),
                        RichEditor::make('initial_content')
                            ->label('Page Content')
                            ->required(fn(?Page $record) => $record === null)
                            ->extraAttributes(['style' => 'min-height: 400px;'])
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
                    ->label('Version Code')
                    ->badge()
                    ->color('success')
                    ->default('N/A'),
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
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
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
            'index' => PageResource\Pages\ListPageResource::route('/'),
            'create' => PageResource\Pages\CreatePageResource::route('/create'),
            'edit' => PageResource\Pages\EditPageResource::route('/{record}/edit'),
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
