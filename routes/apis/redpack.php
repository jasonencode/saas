<?php

use App\Http\Controllers\Redpack\IndexController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

// 红包
Route::group([
    'domain' => config('custom.domains.api_domain'),
    'prefix' => 'redpacks',
], static function (Router $router) {
    // 红包列表
    $router->get('', [IndexController::class, 'index']);
    // 红包详情
    $router->get('{redpack}', [IndexController::class, 'show'])
        ->whereAlphaNumeric('redpack');
});
