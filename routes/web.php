<?php

use App\Http\Controllers\SseController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group([
    'domain' => config('custom.domains.default_domain'),
], static function (Router $router) {
    $router->get('/', function () {
        return view('welcome');
    });
    $router->get('/sse', [SseController::class, 'index']);
});

# 微信服务域名自动验证
Route::get('MP_verify_{code}.txt', static function ($code) {
    return $code;
});
