<?php

use App\Http\Controllers\Campaign\CouponController;
use App\Http\Controllers\Campaign\LotteryController;
use App\Http\Controllers\Campaign\RedpackController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

/*
 * 营销活动模块 API
 * 前缀: /campaign
 * 说明: 优惠券、红包、抽奖等营销活动
 */
Route::group([
    'domain' => config('custom.domains.api_domain'),
    'prefix' => 'campaign',
], static function (Router $router) {
    // ---- 优惠券 ----

    // 可领取的优惠券列表
    $router->get('coupons', [CouponController::class, 'index']);
    // 我的优惠券列表 (需登录)
    $router->get('coupons/my', [CouponController::class, 'mine'])
        ->middleware('auth:sanctum');
    // 优惠券详情 (含使用规则、适用范围)
    $router->get('coupons/{coupon}', [CouponController::class, 'show'])
        ->whereNumber('coupon');
    // 领取优惠券 (需登录)
    $router->post('coupons/{coupon}/claim', [CouponController::class, 'claim'])
        ->middleware('auth:sanctum')
        ->whereNumber('coupon');

    // ---- 红包 ----

    // 红包活动列表
    $router->get('redpacks', [RedpackController::class, 'index']);
    // 我的红包记录 (需登录)
    $router->get('redpacks/my', [RedpackController::class, 'mine'])
        ->middleware('auth:sanctum');
    // 红包活动详情
    $router->get('redpacks/{redpack}', [RedpackController::class, 'show'])
        ->whereNumber('redpack');
    // 通过红包码领取红包 (需登录)
    $router->post('redpacks/{code}/claim', [RedpackController::class, 'claim'])
        ->middleware('auth:sanctum')
        ->whereAlphaNumeric('code');

    // ---- 抽奖 ----

    // 抽奖活动列表
    $router->get('lotteries', [LotteryController::class, 'index']);
    // 抽奖活动详情 (含奖品、规则、参与人数)
    $router->get('lotteries/{lottery}', [LotteryController::class, 'show'])
        ->whereNumber('lottery');
    // 参与抽奖 (需登录，消耗抽奖次数)
    $router->post('lotteries/{lottery}/draw', [LotteryController::class, 'draw'])
        ->middleware('auth:sanctum')
        ->whereNumber('lottery');
    // 我的抽奖记录 (需登录)
    $router->get('lotteries/{lottery}/draws', [LotteryController::class, 'myDraws'])
        ->middleware('auth:sanctum')
        ->whereNumber('lottery');
    // 我的中奖记录 (需登录)
    $router->get('lotteries/{lottery}/prizes', [LotteryController::class, 'myPrizes'])
        ->middleware('auth:sanctum')
        ->whereNumber('lottery');
    // 查询剩余抽奖次数 (需登录)
    $router->get('lotteries/{lottery}/available-draws', [LotteryController::class, 'availableDraws'])
        ->middleware('auth:sanctum')
        ->whereNumber('lottery');
});
