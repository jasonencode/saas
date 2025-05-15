<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group([
    'domain' => config('custom.domains.default_domain'),
], function(Router $router) {
    $router->get('/', function() {
        return view('welcome');
    });
});

# 微信服务域名自动验证
Route::get('MP_verify_{code}.txt', function($code) {
    return $code;
});
