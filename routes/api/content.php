<?php

use App\Http\Controllers\Content\CategoryController;
use App\Http\Controllers\Content\ContentController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group([
    'domain' => config('custom.domains.api_domain'),
], static function (Router $router) {
    $router->get('contents', [ContentController::class, 'index']);
    $router->get('contents/{content}', [ContentController::class, 'show']);

    $router->get('categories', [CategoryController::class, 'index']);
    $router->get('categories/{category}', [CategoryController::class, 'show']);
});
