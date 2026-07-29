<?php

use AnjanTalukdar\PageVersioning\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

$prefix = config('page-versioning.route_prefix', 'pages');
$middleware = config('page-versioning.route_middleware', ['web']);

Route::group([
    'prefix' => $prefix,
    'middleware' => $middleware,
], function () {
    Route::get('/{type}/{slug}', [PageController::class, 'showTyped'])->name('page-versioning.show.typed');
    Route::get('/{slug}', [PageController::class, 'show'])->name('page-versioning.show');
});
