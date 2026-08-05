<?php

use App\Http\Controllers\Content\CategoryController;
use App\Http\Controllers\Content\CommentController;
use App\Http\Controllers\Content\ContentController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

/*
 * 内容模块 API
 * 前缀: /contents
 * 说明: 文章/资讯的浏览与评论
 */
Route::group([
    'domain' => config('custom.domains.api_domain'),
    'prefix' => 'contents',
], static function (Router $router) {
    // ---- 内容 ----

    // 内容列表 (支持按分类、标签筛选，分页)
    $router->get('', [ContentController::class, 'index']);
    // 内容详情 (含正文、作者等完整信息)
    $router->get('{content}', [ContentController::class, 'show'])
        ->whereNumber('content');

    // ---- 评论 ----

    // 获取内容评论列表 (分页)
    $router->get('{content}/comments', [CommentController::class, 'index'])
        ->whereNumber('content');
    // 对内容发表评论 (需登录)
    $router->post('{content}/comments', [CommentController::class, 'store'])
        ->whereNumber('content')
        ->middleware('auth:sanctum');

    // ---- 分类 ----

    // 内容分类列表 (树形结构)
    $router->get('categories', [CategoryController::class, 'index']);
    // 分类详情 (含该分类下的内容列表)
    $router->get('categories/{category}', [CategoryController::class, 'show'])
        ->whereNumber('category');
});
