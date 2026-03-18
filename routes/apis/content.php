<?php

use App\Http\Controllers\Content\CategoryController;
use App\Http\Controllers\Content\ContentController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group([
    'domain' => config('custom.domains.api_domain'),
    'prefix' => 'contents',
], static function (Router $router) {
    $router->get('', [ContentController::class, 'index']);
    $router->get('{content}', [ContentController::class, 'show'])
        ->whereNumber('content');

    $router->get('categories', [CategoryController::class, 'index']);
    $router->get('categories/{category}', [CategoryController::class, 'show'])
        ->whereNumber('category');
});
