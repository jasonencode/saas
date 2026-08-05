<?php

use App\Http\Controllers\Finance\PaymentController;
use App\Http\Controllers\Finance\VoucherController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

/*
 * 财务模块 API
 * 中间件: auth:sanctum (全模块需登录)
 * 说明: 支付、退款、结算凭据等财务相关接口
 */
Route::group([
    'domain' => config('custom.domains.api_domain'),
    'middleware' => ['auth:sanctum'],
], static function (Router $router) {
    // ---- 支付 ----

    $router->group([
        'prefix' => 'payments',
    ], function (Router $router) {
        // 发起支付 (创建支付单，返回支付参数)
        $router->post('', [PaymentController::class, 'store']);
        // 查询支付状态 (轮询或回调确认)
        $router->get('{payment}', [PaymentController::class, 'show'])
            ->whereNumber('payment');
        // 申请退款 (已支付的订单可申请)
        $router->post('{payment}/refund', [PaymentController::class, 'refund'])
            ->whereNumber('payment');
    });

    // ---- 结算凭据 ----

    $router->group([
        'prefix' => 'vouchers',
    ], function (Router $router) {
        // 结算凭据列表 (分页)
        $router->get('', [VoucherController::class, 'index']);
    });
});
