<?php

namespace AnjanTalukdar\PageVersioning\Filament\Resources\PageResource\Pages;

use AnjanTalukdar\PageVersioning\Filament\Resources\PageResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditPageResource extends EditRecord
{
    public static function getResource(): string
    {
        return PageResource::class;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    public function getSubheading(): ?string
    {
        $page = $this->getRecord();
        if ($page && $page->currentVersion) {
            $version = $page->currentVersion;
            $date = $version->published_at ? $version->published_at->format('M d, Y') : 'Unpublished';
            return "Active Version: {$version->version_name} ({$version->version_code}) | Title: {$version->title} | Published: {$date}";
        }

        return "No published version set. Add or publish a revision below in the Version History table.";
    }
}
