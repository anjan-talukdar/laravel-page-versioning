# Laravel Page & Page Versioning Engine (`anjan-talukdar/laravel-page-versioning`)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/anjan-talukdar/laravel-page-versioning.svg?style=flat-square)](https://packagist.org/packages/anjan-talukdar/laravel-page-versioning)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

A lightweight, append-only content management and versioning engine for Laravel applications with optional Filament Admin support. 

Maintains complete revision history for static pages (e.g. Privacy Policy, Terms of Service, Cookie Policy, Refund Policy, About Us, Contact) with draft/publish workflows, soft deletes, and safe rollbacks.

---

## Key Features

- **Append-Only History**: Edits and rollbacks duplicate revisions as brand-new versions without ever overwriting historical records.
- **Dual Support**: Fully functional out-of-the-box in standard Laravel applications (without Filament) or inside Filament Admin dashboards via `PageVersioningPlugin`.
- **Application Status Enum**: Type-safe version status management (`DRAFT`, `PUBLISHED`, `ARCHIVED`).
- **Flexible URL Routing**: Supports direct short URLs (`/pages/privacy-policy`) and optional type-prepended URLs (`/pages/legal/privacy-policy`).
- **Blade Helpers**: Retrieve published page instances or contents in Blade using `page('privacy-policy')`.
- **Centralized Service**: All business logic encapsulated in `PageService`.

---

## Installation

### 1. Require Package via Composer

```bash
composer require anjan-talukdar/laravel-page-versioning
```

### 2. Install Base Package

Run the standard installation command to publish configuration and database migrations:

```bash
php artisan page-versioning:install
```

---

## Filament Admin Integration (Optional)

If your application uses **Filament Admin**, run the dedicated Filament installation command:

```bash
php artisan page-versioning:install-filament
```

Then register the `PageVersioningPlugin` in your panel provider (e.g. `app/Providers/Filament/AdminPanelProvider.php`):

```php
use AnjanTalukdar\PageVersioning\Filament\PageVersioningPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugins([
            PageVersioningPlugin::make()
                ->navigationGroup('Content Management')
                ->navigationIcon('heroicon-o-document-duplicate')
                ->navigationSort(10),
        ]);
}
```

### Customizing Filament Resources

Developers can customize the Filament resources in 3 ways:

1. **Publish Filament Resource Files for Overwrite**:
   ```bash
   php artisan vendor:publish --tag=page-versioning-filament
   ```
   This copies `PageResource.php`, sub-pages, and `PageVersionsRelationManager` directly into `app/Filament/Resources/PageVersioning` for complete editing.

2. **Override Resource Class via Plugin Method**:
   ```php
   PageVersioningPlugin::make()
       ->resource(App\Filament\Resources\CustomPageResource::class)
   ```

3. **Override Resource Class via Config (`config/page-versioning.php`)**:
   ```php
   'filament' => [
       'resources' => [
           'page' => App\Filament\Resources\CustomPageResource::class,
       ],
   ],
   ```

---

## Usage Guide

### Using Blade Helper (Without Filament)

You can retrieve active published pages directly in any Blade view:

```blade
@if($policy = page('privacy-policy'))
    <h1>{{ $policy->currentVersion->title }}</h1>
    <div>{!! $policy->currentVersion->content !!}</div>
    <small>Version: {{ $policy->currentVersion->version_name }} ({{ $policy->currentVersion->version_code }})</small>
@endif
```

### Programmatic Usage via `PageService`

```php
use AnjanTalukdar\PageVersioning\Services\PageService;
use AnjanTalukdar\PageVersioning\Enums\PageVersionStatus;

$service = app(PageService::class);

// 1. Create a logical page with initial version
$page = $service->createPage([
    'type' => 'legal',
    'slug' => 'privacy-policy',
], [
    'title' => 'Privacy Policy',
    'version_name' => 'Initial Release',
    'version_code' => 'v1.0.0',
    'content' => '<p>Privacy policy content...</p>',
], $userId = null, $publishImmediately = true);

// 2. Draft a new revision
$draftVersion = $service->createVersion($page, [
    'title' => 'Privacy Policy (2027 Update)',
    'version_name' => 'DPDP Compliance Update',
    'version_code' => 'v1.1.0',
    'content' => '<p>Updated privacy policy content...</p>',
    'change_summary' => 'Updated data protection compliance terms',
], PageVersionStatus::DRAFT);

// 3. Publish a revision
$service->publishVersion($page, $draftVersion);

// 4. Safe Rollback to a past version
$service->rollbackToVersion($page, $oldVersion, 'Rollback to Initial Release');
```

---

## Configuration (`config/page-versioning.php`)

```php
return [
    'register_routes' => true,
    'route_prefix' => 'pages',
    'route_middleware' => ['web'],
    'layout' => 'layouts.app',
    'default_types' => [
        'legal' => 'Legal & Policies',
        'general' => 'General Information',
    ],
];
```

---

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
