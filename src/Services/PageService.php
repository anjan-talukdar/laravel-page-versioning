<?php

namespace AnjanTalukdar\PageVersioning\Services;

use AnjanTalukdar\PageVersioning\Enums\PageVersionStatus;
use AnjanTalukdar\PageVersioning\Models\Page;
use AnjanTalukdar\PageVersioning\Models\PageVersion;
use Illuminate\Support\Facades\DB;

class PageService
{
    public function getPageModel(): Page
    {
        $class = config('page-versioning.models.page', Page::class);
        return new $class();
    }

    public function getPageVersionModel(): PageVersion
    {
        $class = config('page-versioning.models.page_version', PageVersion::class);
        return new $class();
    }

    public function createPage(array $pageData, array $versionData, ?int $userId = null, bool $publishImmediately = true): Page
    {
        return DB::transaction(function () use ($pageData, $versionData, $userId, $publishImmediately) {
            $page = $this->getPageModel()->create([
                'type' => $pageData['type'] ?? 'general',
                'slug' => $pageData['slug'],
            ]);

            $versionCode = $versionData['version_code'] ?? $this->generateNextVersionCode($page);
            $versionName = $versionData['version_name'] ?? 'Initial Release';

            $version = $page->versions()->create([
                'version_name' => $versionName,
                'version_code' => $versionCode,
                'title' => $versionData['title'],
                'content' => $versionData['content'],
                'change_summary' => $versionData['change_summary'] ?? 'Initial publication',
                'status' => $publishImmediately ? PageVersionStatus::PUBLISHED : PageVersionStatus::DRAFT,
                'published_at' => $publishImmediately ? now() : null,
                'created_by' => $userId,
            ]);

            if ($publishImmediately) {
                $page->update(['current_version_id' => $version->id]);
            }

            return $page->fresh(['currentVersion']);
        });
    }

    public function createVersion(Page $page, array $versionData, PageVersionStatus $status = PageVersionStatus::DRAFT, ?int $userId = null): PageVersion
    {
        return DB::transaction(function () use ($page, $versionData, $status, $userId) {
            $versionCode = $versionData['version_code'] ?? $this->generateNextVersionCode($page);

            $version = $page->versions()->create([
                'version_name' => $versionData['version_name'] ?? "Revision {$versionCode}",
                'version_code' => $versionCode,
                'title' => $versionData['title'] ?? ($page->currentVersion?->title ?? $page->slug),
                'content' => $versionData['content'],
                'change_summary' => $versionData['change_summary'] ?? null,
                'status' => $status,
                'published_at' => $status === PageVersionStatus::PUBLISHED ? now() : null,
                'created_by' => $userId,
            ]);

            if ($status === PageVersionStatus::PUBLISHED) {
                $this->publishVersion($page, $version);
            }

            return $version;
        });
    }

    public function publishVersion(Page $page, PageVersion $version): Page
    {
        return DB::transaction(function () use ($page, $version) {
            // Set all currently published versions for this page to ARCHIVED
            $page->versions()
                ->where('status', PageVersionStatus::PUBLISHED)
                ->where('id', '!=', $version->id)
                ->update(['status' => PageVersionStatus::ARCHIVED]);

            // Mark this target version as PUBLISHED
            $version->update([
                'status' => PageVersionStatus::PUBLISHED,
                'published_at' => $version->published_at ?? now(),
            ]);

            // Update page's current_version_id
            $page->update(['current_version_id' => $version->id]);

            return $page->fresh(['currentVersion']);
        });
    }

    public function rollbackToVersion(Page $page, PageVersion $targetVersion, ?string $customVersionName = null, ?int $userId = null): PageVersion
    {
        return DB::transaction(function () use ($page, $targetVersion, $customVersionName, $userId) {
            $nextVersionCode = $this->generateNextVersionCode($page);
            $versionName = $customVersionName ?? ("Rollback to " . ($targetVersion->version_name ?: $targetVersion->version_code));

            $newVersion = $targetVersion->duplicateAsNewVersion($nextVersionCode, $versionName, $userId);

            $this->publishVersion($page, $newVersion);

            return $newVersion;
        });
    }

    public function getPageBySlug(string $slug, ?string $type = null): ?Page
    {
        $query = $this->getPageModel()
            ->with(['currentVersion'])
            ->where('slug', $slug);

        if ($type !== null) {
            $query->where('type', $type);
        }

        $page = $query->first();

        if ($page && $page->isPublished()) {
            return $page;
        }

        return null;
    }

    public function generateNextVersionCode(Page $page): string
    {
        $latestVersion = $page->versions()->orderBy('id', 'desc')->first();

        if (!$latestVersion || !preg_match('/^v?(\d+)\.(\d+)\.(\d+)$/i', $latestVersion->version_code, $matches)) {
            return 'v1.0.0';
        }

        $major = (int) $matches[1];
        $minor = (int) $matches[2] + 1;
        $patch = 0;

        return "v{$major}.{$minor}.{$patch}";
    }
}
