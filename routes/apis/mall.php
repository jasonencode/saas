<?php

use App\Http\Controllers\Mall\CategoryController;
use App\Http\Controllers\Mall\ProductController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group([
    'domain' => config('custom.domains.api_domain'),
    'prefix' => 'mall',
], static function (Router $router) {
    $router->get('categories', [CategoryController::class, 'index']);
    $router->get('categories/{category}', [CategoryController::class, 'show'])
        ->whereNumber('category');

    $router->get('products', [ProductController::class, 'index']);
});
