<?php

namespace AnjanTalukdar\PageVersioning\Filament\Resources\PageResource\Pages;

use AnjanTalukdar\PageVersioning\Filament\Resources\PageResource;
use AnjanTalukdar\PageVersioning\Models\Page;
use AnjanTalukdar\PageVersioning\Services\PageService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreatePageResource extends CreateRecord
{
    public static function getResource(): string
    {
        return PageResource::class;
    }

    protected function handleRecordCreation(array $data): Model
    {
        /** @var PageService $pageService */
        $pageService = app(PageService::class);

        $pageData = [
            'type' => $data['type'] ?? 'general',
            'slug' => $data['slug'],
        ];

        $versionData = [
            'title' => $data['initial_title'] ?? $data['slug'],
            'version_name' => $data['initial_version_name'] ?? 'Initial Release',
            'version_code' => $data['initial_version_code'] ?? 'v1.0.0',
            'content' => $data['initial_content'] ?? '',
            'change_summary' => $data['initial_change_summary'] ?? 'Initial version creation',
        ];

        $userId = Auth::id();
        $publishImmediately = (bool) ($data['initial_publish'] ?? true);

        return $pageService->createPage($pageData, $versionData, $userId, $publishImmediately);
    }
}
