<?php

use App\Http\Controllers\Finance\PaymentController;
use App\Http\Controllers\Finance\RechargeController;
use App\Http\Controllers\Finance\VoucherController;
use App\Http\Controllers\Finance\WithdrawController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

/*
 * 财务模块 API
 * 中间件: auth:sanctum (全模块需登录)
 * 说明: 支付、退款、结算凭据等财务相关接口
 */
Route::group([
    'domain' => config('custom.domains.api_domain'),
], static function (Router $router) {
    // ---- 支付回调（无需登录） ----
    $router->post('payments/{payment}/notify', [PaymentController::class, 'notify'])
        ->whereNumber('payment')
        ->name('payments.notify');
});

Route::group([
    'domain' => config('custom.domains.api_domain'),
    'middleware' => ['auth:sanctum'],
], static function (Router $router) {
    // ---- 支付 ----

    $router->group([
        'prefix' => 'payments',
    ], function (Router $router) {
        // 创建支付单
        $router->post('', [PaymentController::class, 'store']);
        // 查询支付状态
        $router->get('{payment}', [PaymentController::class, 'show'])
            ->whereNumber('payment');
        // 发起支付（获取支付参数）
        $router->post('{payment}/pay', [PaymentController::class, 'pay'])
            ->whereNumber('payment');
        // 申请退款
        $router->post('{payment}/refund', [PaymentController::class, 'refund'])
            ->whereNumber('payment');
    });

    // ---- 充值 ----

    $router->group([
        'prefix' => 'recharge',
    ], function (Router $router) {
        // 充值订单列表
        $router->get('', [RechargeController::class, 'index']);
        // 创建充值订单
        $router->post('', [RechargeController::class, 'store']);
        // 查询充值订单状态
        $router->get('{order}', [RechargeController::class, 'show'])
            ->whereNumber('order');
    });

    // ---- 提现 ----

    $router->group([
        'prefix' => 'withdraw',
    ], function (Router $router) {
        // 提现订单列表
        $router->get('', [WithdrawController::class, 'index']);
        // 创建提现订单
        $router->post('', [WithdrawController::class, 'store']);
        // 查询提现订单状态
        $router->get('{order}', [WithdrawController::class, 'show'])
            ->whereNumber('order');
        // 取消提现订单
        $router->post('{order}/cancel', [WithdrawController::class, 'cancel'])
            ->whereNumber('order');
        // 获取可提现余额
        $router->get('balance', [WithdrawController::class, 'balance']);
    });

    // ---- 结算凭据 ----

    $router->group([
        'prefix' => 'vouchers',
    ], function (Router $router) {
        // 结算凭据列表 (分页)
        $router->get('', [VoucherController::class, 'index']);
    });
});
