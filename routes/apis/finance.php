<?php

use App\Http\Controllers\Finance\PaymentController;
use App\Http\Controllers\Finance\VoucherController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group([
    'domain' => config('custom.domains.api_domain'),
    'middleware' => ['auth:sanctum'],
], static function (Router $router) {
    // 支付相关
    $router->group([
        'prefix' => 'payments',
    ], function (Router $router) {
        // 发起支付
        $router->post('', [PaymentController::class, 'store']);
        // 查询支付状态
        $router->get('{payment}', [PaymentController::class, 'show'])
            ->whereNumber('payment');
        // 申请退款
        $router->post('{payment}/refund', [PaymentController::class, 'refund'])
            ->whereNumber('payment');
    });

    // 结算凭据
    $router->group([
        'prefix' => 'vouchers',
    ], function (Router $router) {
        // 结算凭据列表
        $router->get('', [VoucherController::class, 'index']);
    });
});
