<?php

namespace AnjanTalukdar\PageVersioning\Models;

use AnjanTalukdar\PageVersioning\Enums\PageVersionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type',
        'slug',
        'current_version_id',
    ];

    public function getTable(): string
    {
        return config('page-versioning.tables.pages', 'pages');
    }

    public function versions(): HasMany
    {
        $versionModel = config('page-versioning.models.page_version', PageVersion::class);
        return $this->hasMany($versionModel, 'page_id');
    }

    public function currentVersion(): BelongsTo
    {
        $versionModel = config('page-versioning.models.page_version', PageVersion::class);
        return $this->belongsTo($versionModel, 'current_version_id');
    }

    public function publishedVersions(): HasMany
    {
        return $this->versions()->where('status', PageVersionStatus::PUBLISHED);
    }

    public function draftVersions(): HasMany
    {
        return $this->versions()->where('status', PageVersionStatus::DRAFT);
    }

    public function archivedVersions(): HasMany
    {
        return $this->versions()->where('status', PageVersionStatus::ARCHIVED);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeBySlug(Builder $query, string $slug): Builder
    {
        return $query->where('slug', $slug);
    }

    public function scopeWithPublishedVersion(Builder $query): Builder
    {
        return $query->whereNotNull('current_version_id')
            ->whereHas('currentVersion', fn (Builder $q) => $q->where('status', PageVersionStatus::PUBLISHED));
    }

    public function getUrl(bool $includeType = false): string
    {
        $prefix = config('page-versioning.route_prefix', 'pages');
        $prefixStr = $prefix ? trim($prefix, '/') . '/' : '';

        if ($includeType && !empty($this->type)) {
            return url("{$prefixStr}{$this->type}/{$this->slug}");
        }

        return url("{$prefixStr}{$this->slug}");
    }

    public function getCurrentVersionName(): ?string
    {
        return $this->currentVersion?->version_name;
    }

    public function getCurrentVersionCode(): ?string
    {
        return $this->currentVersion?->version_code;
    }

    public function isPublished(): bool
    {
        return !is_null($this->current_version_id) &&
            $this->currentVersion &&
            $this->currentVersion->isPublished();
    }

    public function hasDrafts(): bool
    {
        return $this->draftVersions()->exists();
    }

    public function latestVersion(): ?PageVersion
    {
        return $this->versions()->orderBy('id', 'desc')->first();
    }
}
