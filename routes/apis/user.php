<?php

use App\Http\Controllers\User\AccountController;
use App\Http\Controllers\User\AddressController;
use App\Http\Controllers\User\IdentityController;
use App\Http\Controllers\User\InvoiceController;
use App\Http\Controllers\User\InvoiceTitleController;
use App\Http\Controllers\User\NotificationController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\SafeController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

/*
 * 用户模块 API
 * 前缀: /user
 * 中间件: auth:sanctum (全模块需登录)
 * 说明: 个人中心相关接口，包括资料、账户、安全、地址、通知、发票、身份等
 */
Route::group([
    'domain' => config('custom.domains.api_domain'),
    'prefix' => 'user',
    'middleware' => ['auth:sanctum'],
], static function (Router $router) {
    // ---- 个人资料 ----

    // 获取用户资料
    $router->get('profile', [ProfileController::class, 'index']);
    // 修改用户资料 (昵称、头像等)
    $router->put('profile', [ProfileController::class, 'update']);

    // ---- 账户 ----

    // 账户信息 (余额、积分等)
    $router->get('account', [AccountController::class, 'index']);
    // 账户变动日志 (充值、消费、退款等明细)
    $router->get('account/logs', [AccountController::class, 'logs']);

    // ---- 安全 ----

    // 登录记录 (近期登录设备、IP)
    $router->get('safe/records', [SafeController::class, 'records']);
    // 修改密码
    $router->put('safe/password', [SafeController::class, 'password']);
    // 登出当前会话
    $router->post('safe/logout', [SafeController::class, 'logout']);

    // ---- 收货地址 ----

    $router->group([
        'prefix' => 'addresses',
    ], function (Router $router) {
        // 地址列表
        $router->get('', [AddressController::class, 'index']);
        // 地址详情
        $router->get('{address}', [AddressController::class, 'show'])
            ->whereNumber('address');
        // 获取省市区三级联动数据
        $router->get('regions', [AddressController::class, 'regions']);
        // 新增地址
        $router->post('', [AddressController::class, 'store']);
        // 编辑地址
        $router->put('{address}', [AddressController::class, 'update'])
            ->whereNumber('address');
        // 删除地址
        $router->delete('{address}', [AddressController::class, 'destroy'])
            ->whereNumber('address');
        // 设置默认地址
        $router->put('{address}/default', [AddressController::class, 'setDefault'])
            ->whereNumber('address');
    });

    // ---- 通知 ----

    $router->group([
        'prefix' => 'notifications',
    ], function (Router $router) {
        // 通知列表 (分页，按时间倒序)
        $router->get('', [NotificationController::class, 'index']);
        // 通知分组列表 (按类型分组统计)
        $router->get('group', [NotificationController::class, 'group']);
        // 通知详情
        $router->get('{notification}', [NotificationController::class, 'show'])
            ->whereUuid('notification');
        // 单条标记已读
        $router->put('{notification}/read', [NotificationController::class, 'markAsRead'])
            ->whereUuid('notification');
        // 全部标记已读
        $router->put('read', [NotificationController::class, 'markAllAsRead']);
        // 获取未读通知数量
        $router->get('count', [NotificationController::class, 'count']);
        // 删除全部已读通知
        $router->delete('read', [NotificationController::class, 'deleteAllRead']);
        // 删除单条通知
        $router->delete('{notification}', [NotificationController::class, 'destroy'])
            ->whereUuid('notification');
    });

    // ---- 发票抬头 ----

    $router->group([
        'prefix' => 'invoice-titles',
    ], function (Router $router) {
        // 发票抬头列表
        $router->get('', [InvoiceTitleController::class, 'index']);
        // 发票抬头详情
        $router->get('{invoiceTitle}', [InvoiceTitleController::class, 'show'])
            ->whereNumber('invoiceTitle');
        // 新增发票抬头
        $router->post('', [InvoiceTitleController::class, 'store']);
        // 编辑发票抬头
        $router->put('{invoiceTitle}', [InvoiceTitleController::class, 'update'])
            ->whereNumber('invoiceTitle');
        // 删除发票抬头
        $router->delete('{invoiceTitle}', [InvoiceTitleController::class, 'destroy'])
            ->whereNumber('invoiceTitle');
        // 设置默认发票抬头
        $router->put('{invoiceTitle}/default', [InvoiceTitleController::class, 'setDefault'])
            ->whereNumber('invoiceTitle');
    });

    // ---- 发票 ----

    $router->group([
        'prefix' => 'invoices',
    ], function (Router $router) {
        // 可开票订单列表
        $router->get('orders', [InvoiceController::class, 'invoicableOrders']);
        // 发票申请列表
        $router->get('applications', [InvoiceController::class, 'applications']);
        // 发票申请详情
        $router->get('applications/{application}', [InvoiceController::class, 'application'])
            ->whereNumber('application');
        // 提交发票申请
        $router->post('applications', [InvoiceController::class, 'apply']);
        // 已开具发票列表
        $router->get('', [InvoiceController::class, 'invoices']);
        // 发票详情 (含下载链接)
        $router->get('{invoice}', [InvoiceController::class, 'invoice'])
            ->whereNumber('invoice');
    });

    // ---- 身份管理 ----

    $router->group([
        'prefix' => 'identities',
    ], function (Router $router) {
        // 当前用户有效身份列表
        $router->get('', [IdentityController::class, 'index']);
        // 可订阅/购买的身份列表
        $router->get('available/{tenantId}', [IdentityController::class, 'available'])
            ->whereNumber('tenantId');
        // 检查是否持有指定身份
        $router->get('{identity}/check', [IdentityController::class, 'check'])
            ->whereNumber('identity');
    });
});
