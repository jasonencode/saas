<?php

use App\Http\Controllers\Content\CategoryController;
use App\Http\Controllers\Content\CommentController;
use App\Http\Controllers\Content\ContentController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group([
    'domain' => config('custom.domains.api_domain'),
    'prefix' => 'contents',
], static function (Router $router) {
    // 内容列表
    $router->get('', [ContentController::class, 'index']);
    // 内容详情
    $router->get('{content}', [ContentController::class, 'show'])
        ->whereNumber('content');

    // 获取内容评论列表
    $router->get('{content}/comments', [CommentController::class, 'index'])
        ->whereNumber('content');
    // 对内容发表评论
    $router->post('{content}/comments', [CommentController::class, 'store'])
        ->whereNumber('content')
        ->middleware('auth:sanctum');

    // 分类列表
    $router->get('categories', [CategoryController::class, 'index']);
    // 分类详情
    $router->get('categories/{category}', [CategoryController::class, 'show'])
        ->whereNumber('category');
});
