<?php

use App\Http\Controllers\System\AppVersionController;
use App\Http\Controllers\System\UploadController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

/*
 * 系统模块 API
 * 前缀: /system (上传接口) / 无前缀 (版本检查)
 * 说明: 文件上传、应用版本检查等系统级接口
 */

// ---- 公开接口 (无需登录) ----

Route::group([
    'domain' => config('custom.domains.api_domain'),
], static function (Router $router) {
    // 获取当前应用版本
    $router->get('app_version', [AppVersionController::class, 'index']);
});

// ---- 需登录接口 ----

Route::group([
    'domain' => config('custom.domains.api_domain'),
    'prefix' => 'system',
    'middleware' => ['auth:sanctum'],
], static function (Router $router) {
    // ---- 上传 ----

    // 上传单张图片
    $router->post('upload/image', [UploadController::class, 'image']);
    // 上传多张图片
    $router->post('upload/images', [UploadController::class, 'images']);
});
