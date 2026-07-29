<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Package Route Configuration
    |--------------------------------------------------------------------------
    */
    'register_routes' => true,
    'route_prefix' => 'pages',
    'route_middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Default Page Layout
    |--------------------------------------------------------------------------
    |
    | The default Blade layout to extend in the built-in view.
    |
    */
    'layout' => 'layouts.app',

    /*
    |--------------------------------------------------------------------------
    | Page Categories / Types
    |--------------------------------------------------------------------------
    */
    'default_types' => [
        'legal' => 'Legal & Policies',
        'general' => 'General Information',
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Definitions
    |--------------------------------------------------------------------------
    */
    'models' => [
        'page' => \AnjanTalukdar\PageVersioning\Models\Page::class,
        'page_version' => \AnjanTalukdar\PageVersioning\Models\PageVersion::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Table Names
    |--------------------------------------------------------------------------
    */
    'tables' => [
        'pages' => 'pages',
        'page_versions' => 'page_versions',
    ],

    /*
    |--------------------------------------------------------------------------
    | Filament Admin Integration Settings
    |--------------------------------------------------------------------------
    */
    'filament' => [
        'navigation_group' => 'Content Management',
        'navigation_icon' => 'heroicon-o-document-duplicate',
        'navigation_sort' => 10,

        // Custom resource class override option
        'resources' => [
            'page' => \AnjanTalukdar\PageVersioning\Filament\Resources\PageResource::class,
        ],
    ],
];
