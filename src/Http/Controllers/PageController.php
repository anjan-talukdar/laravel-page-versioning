<?php

namespace AnjanTalukdar\PageVersioning\Http\Controllers;

use AnjanTalukdar\PageVersioning\Models\Page;
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
        $view = $this->resolveView($page);

        return view($view, compact('page', 'version', 'layout'));
    }

    public function showTyped(string $type, string $slug)
    {
        $page = $this->pageService->getPageBySlug($slug, $type);

        if (!$page) {
            abort(404, 'Page not found or unavailable.');
        }

        $version = $page->currentVersion;
        $layout = config('page-versioning.layout', 'layouts.app');
        $view = $this->resolveView($page);

        return view($view, compact('page', 'version', 'layout'));
    }

    protected function resolveView(Page $page): string
    {
        // 1. Check for slug-specific template (e.g. resources/views/pages/about-us.blade.php)
        if (view()->exists("pages.{$page->slug}")) {
            return "pages.{$page->slug}";
        }

        // 2. Check for type-specific template (e.g. resources/views/pages/legal.blade.php)
        if (view()->exists("pages.{$page->type}")) {
            return "pages.{$page->type}";
        }

        // 3. Default package view namespace (checks resources/views/vendor/anjan-talukdar/laravel-page-versioning/show.blade.php first)
        return 'laravel-page-versioning::show';
    }
}
