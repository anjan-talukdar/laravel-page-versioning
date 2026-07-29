<?php

namespace AnjanTalukdar\PageVersioning\Services;

use AnjanTalukdar\PageVersioning\Enums\PageVersionStatus;
use AnjanTalukdar\PageVersioning\Models\Page;
use AnjanTalukdar\PageVersioning\Models\PageVersion;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PageService
{
    /**
     * Get configured Page class model
     */
    protected function pageModel(): string
    {
        return config('page-versioning.models.page', Page::class);
    }

    /**
     * Get configured PageVersion class model
     */
    protected function versionModel(): string
    {
        return config('page-versioning.models.page_version', PageVersion::class);
    }

    /**
     * Generate the next auto-incrementing integer version code for a page.
     */
    public function generateNextVersionCode(Page $page): int
    {
        $maxCode = $page->versions()->max('version_code');
        return ($maxCode ?? 0) + 1;
    }

    /**
     * Create a new page along with its initial version.
     */
    public function createPage(array $pageData, array $versionData, ?int $userId = null, bool $publishImmediately = true): Page
    {
        return DB::transaction(function () use ($pageData, $versionData, $userId, $publishImmediately) {
            $pageClass = $this->pageModel();

            /** @var Page $page */
            $page = $pageClass::create([
                'type' => $pageData['type'] ?? 'general',
                'slug' => $pageData['slug'],
            ]);

            $versionCode = 1;
            $versionName = $versionData['version_name'] ?? 'v1.0.0';

            $version = $this->createVersionInternal($page, array_merge($versionData, [
                'version_code' => $versionCode,
                'version_name' => $versionName,
            ]), $userId, $publishImmediately ? PageVersionStatus::PUBLISHED : PageVersionStatus::DRAFT);

            if ($publishImmediately) {
                $page->update(['current_version_id' => $version->id]);
            }

            return $page->fresh(['currentVersion', 'versions']);
        });
    }

    /**
     * Create a new version revision for an existing page.
     */
    public function createVersion(Page $page, array $versionData, ?int $userId = null, PageVersionStatus $status = PageVersionStatus::DRAFT): PageVersion
    {
        return DB::transaction(function () use ($page, $versionData, $userId, $status) {
            $nextCode = $this->generateNextVersionCode($page);
            $versionName = $versionData['version_name'] ?? ("v" . $nextCode . ".0.0");

            $version = $this->createVersionInternal($page, array_merge($versionData, [
                'version_code' => $nextCode,
                'version_name' => $versionName,
            ]), $userId, $status);

            if ($status === PageVersionStatus::PUBLISHED) {
                $this->publishVersion($page, $version);
            }

            return $version;
        });
    }

    /**
     * Internal helper to persist a version.
     */
    protected function createVersionInternal(Page $page, array $data, ?int $userId, PageVersionStatus $status): PageVersion
    {
        $versionClass = $this->versionModel();

        return $versionClass::create([
            'page_id' => $page->id,
            'version_name' => $data['version_name'],
            'version_code' => $data['version_code'],
            'title' => $data['title'],
            'content' => $data['content'],
            'change_summary' => $data['change_summary'] ?? null,
            'status' => $status,
            'published_at' => $status === PageVersionStatus::PUBLISHED ? now() : null,
            'created_by' => $userId,
        ]);
    }

    /**
     * Publish a draft or existing version, marking previous published versions as ARCHIVED.
     */
    public function publishVersion(Page $page, PageVersion $version): bool
    {
        if ((int) $version->page_id !== (int) $page->id) {
            throw new InvalidArgumentException("Version ID {$version->id} does not belong to Page ID {$page->id}.");
        }

        return DB::transaction(function () use ($page, $version) {
            // Archive previous published versions
            $page->versions()
                ->where('status', PageVersionStatus::PUBLISHED)
                ->where('id', '!=', $version->id)
                ->update(['status' => PageVersionStatus::ARCHIVED]);

            // Update target version to PUBLISHED
            $version->update([
                'status' => PageVersionStatus::PUBLISHED,
                'published_at' => $version->published_at ?? now(),
            ]);

            // Set current version pointer on page
            $page->update(['current_version_id' => $version->id]);

            return true;
        });
    }

    /**
     * Append-Only Rollback: Duplicates a past version as a NEW auto-incremented revision.
     */
    public function rollbackToVersion(Page $page, PageVersion $targetVersion, ?string $reason = null, ?int $userId = null, bool $publishImmediately = true): PageVersion
    {
        if ((int) $targetVersion->page_id !== (int) $page->id) {
            throw new InvalidArgumentException("Target version does not belong to this page.");
        }

        return DB::transaction(function () use ($page, $targetVersion, $reason, $userId, $publishImmediately) {
            $nextCode = $this->generateNextVersionCode($page);
            $newVersionName = "Rollback to " . ($targetVersion->version_name ?: "Rev #" . $targetVersion->version_code);

            $newVersion = $targetVersion->duplicateAsNewVersion($nextCode, $newVersionName, $userId);

            if ($reason) {
                $newVersion->update([
                    'change_summary' => "Rollback to version revision #{$targetVersion->version_code} ({$targetVersion->version_name}): {$reason}",
                ]);
            }

            if ($publishImmediately) {
                $this->publishVersion($page, $newVersion);
            }

            return $newVersion;
        });
    }

    /**
     * Retrieve a published page by slug (and optional type).
     */
    public function getPageBySlug(string $slug, ?string $type = null): ?Page
    {
        $pageClass = $this->pageModel();

        $query = $pageClass::query()
            ->where('slug', $slug)
            ->with(['currentVersion' => fn ($q) => $q->where('status', PageVersionStatus::PUBLISHED)]);

        if ($type !== null) {
            $query->where('type', $type);
        }

        /** @var Page|null $page */
        $page = $query->first();

        if (!$page || !$page->currentVersion) {
            return null;
        }

        return $page;
    }
}
