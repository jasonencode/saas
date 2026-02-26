<?php

use App\Http\Controllers\Auth\CaptchaController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SmsController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group([
    'domain' => config('custom.domains.api_domain'),
    'prefix' => 'auth',
], static function (Router $router) {
    # 图形验证码
    $router->get('captcha', [CaptchaController::class, 'index']);

    # 登录
    $router->post('password', [LoginController::class, 'password']);
    $router->post('tenant', [LoginController::class, 'tenant']);

    # 注册
    $router->post('register', [RegisterController::class, 'index']);

    # 发送短信验证码
    $router->post('sms', [SmsController::class, 'send']);
});
