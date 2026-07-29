<?php

namespace AnjanTalukdar\PageVersioning\Http\Controllers;

use AnjanTalukdar\PageVersioning\Services\PageService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PageController extends Controller
{
    public function __construct(
        protected PageService $pageService
    ) {}

    public function show(string $slug)
    {
        $page = $this->pageService->getPageBySlug($slug);

        if (!$page) {
            abort(404, 'Page not found or unavailable.');
        }

        $version = $page->currentVersion;
        $layout = config('page-versioning.layout', 'layouts.app');

        return view('page-versioning::show', compact('page', 'version', 'layout'));
    }

    public function showTyped(string $type, string $slug)
    {
        $page = $this->pageService->getPageBySlug($slug, $type);

        if (!$page) {
            abort(404, 'Page not found or unavailable.');
        }

        $version = $page->currentVersion;
        $layout = config('page-versioning.layout', 'layouts.app');

        return view('page-versioning::show', compact('page', 'version', 'layout'));
    }
}
