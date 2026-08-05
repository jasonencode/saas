<?php

use App\Http\Controllers\Auth\CaptchaController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\MiniProgramController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SmsController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

/*
 * 认证模块 API
 * 前缀: /auth
 * 说明: 登录、注册、验证码等公开接口，无需登录
 */
Route::group([
    'domain' => config('custom.domains.api_domain'),
    'prefix' => 'auth',
], static function (Router $router) {
    // ---- 验证码 ----

    // 获取图形验证码 (含验证码 key，用于后续校验)
    $router->get('captcha', [CaptchaController::class, 'index']);
    // 发送短信验证码
    $router->post('sms', [SmsController::class, 'send']);

    // ---- 登录 ----

    // 账号密码登录
    $router->post('password', [LoginController::class, 'password']);
    // 租户登录 (切换租户身份)
    $router->post('tenant', [LoginController::class, 'tenant']);
    // 微信小程序手机号快捷登录
    $router->post('mini/phone', [MiniProgramController::class, 'phone']);

    // ---- 注册 ----

    // 用户注册 (手机号 + 短信验证码)
    $router->post('register', [RegisterController::class, 'index']);
});
