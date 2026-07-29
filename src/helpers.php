<?php

use AnjanTalukdar\PageVersioning\Models\Page;
use AnjanTalukdar\PageVersioning\Services\PageService;

if (!function_exists('page')) {
    /**
     * Retrieve active published Page model or null.
     */
    function page(string $slug, ?string $type = null): ?Page
    {
        /** @var PageService $service */
        $service = app(PageService::class);
        return $service->getPageBySlug($slug, $type);
    }
}
