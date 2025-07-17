<?php

use App\Http\Controllers\Auth\CaptchaController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SmsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Content\CategoryController;
use App\Http\Controllers\Content\ContentController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\User\AddressController;
use App\Http\Controllers\User\InfoController;
use App\Http\Controllers\User\NotificationController;
use App\Http\Controllers\User\SafeController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group([
    'domain' => config('custom.domains.api_domain'),
], function() {

});

# 需要鉴权的共用接口
Route::group([
    'middleware' => ['auth:sanctum'],
    'domain' => config('custom.domains.api_domain'),
], static function(Router $router) {
    $router->post('auth/logout', [AuthController::class, 'logout']);
    # 上传相关接口
    $router->post('upload/image', [UploadController::class, 'image']);
    $router->post('upload/images', [UploadController::class, 'images']);
});

# 不需要鉴权的共用接口
Route::group([
    'domain' => config('custom.domains.api_domain'),
], static function(Router $router) {
    $router->get('test', [TestController::class, 'index']);
    $router->get('regions', [AddressController::class, 'regions']);

    $router->get('categories', [CategoryController::class, 'index']);
    $router->get('contents', [ContentController::class, 'index']);
    $router->get('contents/{content}', [ContentController::class, 'show']);
});

# 登录相关接口
Route::group([
    'prefix' => 'auth',
    'domain' => config('custom.domains.api_domain'),
], static function(Router $router) {
    $router->post('login/password', [PasswordController::class, 'login']);
    $router->post('register', [RegisterController::class, 'index']);
    $router->post('sms/send', [SmsController::class, 'send'])
        ->middleware('throttle:sms');
    $router->get('captcha', [CaptchaController::class, 'index']);
});

# 用户相关接口
Route::group([
    'middleware' => ['auth:sanctum'],
    'prefix' => 'user',
    'domain' => config('custom.domains.api_domain'),
], static function(Router $router) {
    $router->get('info', [InfoController::class, 'index']);
    $router->put('info', [InfoController::class, 'update']);
    # 用户地址管理相关接口
    $router->post('addresses/{address}/default', [AddressController::class, 'setDefault']);
    $router->apiResource('addresses', AddressController::class);
});

# 安全相关接口
Route::group([
    'middleware' => ['auth:sanctum'],
    'prefix' => 'safe',
    'domain' => config('custom.domains.api_domain'),
], static function(Router $router) {
    # 修改密码
    $router->put('password', [SafeController::class, 'password']);
    # 登录记录
    $router->get('records', [SafeController::class, 'records']);
});

# 通知相关接口
Route::group([
    'middleware' => ['auth:sanctum'],
    'prefix' => 'notifications',
    'domain' => config('custom.domains.api_domain'),
], static function(Router $router) {
    $router->get('group', [NotificationController::class, 'group']);
    # 我的通知
    $router->get('', [NotificationController::class, 'index']);
    $router->get('count', [NotificationController::class, 'count']);
    $router->get('{notification}', [NotificationController::class, 'show']);
    # 标记已读
    $router->post('{notification}', [NotificationController::class, 'markAsRead']);
    $router->post('all', [NotificationController::class, 'maskAllAsRead']);
    $router->delete('{notification}', [NotificationController::class, 'destroy']);
    # 删除全部已读
    $router->delete('all', [NotificationController::class, 'deleteAllRead']);
});
