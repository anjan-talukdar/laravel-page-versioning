<?php

namespace AnjanTalukdar\PageVersioning\Models;

use AnjanTalukdar\PageVersioning\Enums\PageVersionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PageVersion extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'page_id',
        'version_name',
        'version_code',
        'title',
        'content',
        'change_summary',
        'status',
        'published_at',
        'created_by',
    ];

    protected $casts = [
        'version_code' => 'integer',
        'status' => PageVersionStatus::class,
        'published_at' => 'datetime',
    ];

    public function getTable(): string
    {
        return config('page-versioning.tables.page_versions', 'page_versions');
    }

    public function page(): BelongsTo
    {
        $pageModel = config('page-versioning.models.page', Page::class);
        return $this->belongsTo($pageModel, 'page_id');
    }

    public function creator(): BelongsTo
    {
        $userModel = config('auth.providers.users.model', 'App\\Models\\User');
        return $this->belongsTo($userModel, 'created_by');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PageVersionStatus::PUBLISHED);
    }

    public function scopeDrafts(Builder $query): Builder
    {
        return $query->where('status', PageVersionStatus::DRAFT);
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', PageVersionStatus::ARCHIVED);
    }

    public function isCurrent(): bool
    {
        return $this->page && (int) $this->page->current_version_id === (int) $this->id;
    }

    public function isPublished(): bool
    {
        return $this->status === PageVersionStatus::PUBLISHED;
    }

    public function isDraft(): bool
    {
        return $this->status === PageVersionStatus::DRAFT;
    }

    public function isArchived(): bool
    {
        return $this->status === PageVersionStatus::ARCHIVED;
    }

    public function duplicateAsNewVersion(int $newVersionCode, ?string $newVersionName = null, ?int $userId = null): self
    {
        return self::create([
            'page_id' => $this->page_id,
            'version_name' => $newVersionName ?? ("Rollback to " . ($this->version_name ?: "Rev #" . $this->version_code)),
            'version_code' => $newVersionCode,
            'title' => $this->title,
            'content' => $this->content,
            'change_summary' => "Restored from version revision #{$this->version_code} ({$this->version_name})",
            'status' => PageVersionStatus::DRAFT,
            'published_at' => null,
            'created_by' => $userId,
        ]);
    }
}
