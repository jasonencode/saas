<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

// API 主域名路由组
Route::group([
    'domain' => config('custom.domains.api_domain'),
], static function (Router $router) {
    // 服务器健康检查
    $router->get('/', fn () => 'Server is working');
});
