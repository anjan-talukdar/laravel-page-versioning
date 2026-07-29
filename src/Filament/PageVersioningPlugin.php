<?php

namespace AnjanTalukdar\PageVersioning\Filament;

use AnjanTalukdar\PageVersioning\Filament\Resources\PageResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

class PageVersioningPlugin implements Plugin
{
    protected ?string $navigationGroup = null;
    protected ?string $navigationIcon = null;
    protected ?int $navigationSort = null;
    protected ?string $resourceClass = null;

    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'laravel-page-versioning';
    }

    public function resource(string $resourceClass): static
    {
        $this->resourceClass = $resourceClass;
        return $this;
    }

    public function navigationGroup(?string $group): static
    {
        $this->navigationGroup = $group;
        return $this;
    }

    public function navigationIcon(?string $icon): static
    {
        $this->navigationIcon = $icon;
        return $this;
    }

    public function navigationSort(?int $sort): static
    {
        $this->navigationSort = $sort;
        return $this;
    }

    public function getNavigationGroup(): string
    {
        return $this->navigationGroup ?? config('page-versioning.filament.navigation_group', 'Content Management');
    }

    public function getNavigationIcon(): string
    {
        return $this->navigationIcon ?? config('page-versioning.filament.navigation_icon', 'heroicon-o-document-duplicate');
    }

    public function getNavigationSort(): ?int
    {
        return $this->navigationSort ?? config('page-versioning.filament.navigation_sort', 10);
    }

    public function getResourceClass(): string
    {
        return $this->resourceClass
            ?? config('page-versioning.filament.resources.page', PageResource::class);
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            $this->getResourceClass(),
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
