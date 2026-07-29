<?php

namespace AnjanTalukdar\PageVersioning\Filament\Resources\PageResource\Pages;

use AnjanTalukdar\PageVersioning\Filament\Resources\PageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPageResource extends ListRecords
{
    public static function getResource(): string
    {
        return PageResource::class;
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
