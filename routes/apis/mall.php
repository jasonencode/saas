<?php

use App\Http\Controllers\Mall\CartController;
use App\Http\Controllers\Mall\CategoryController;
use App\Http\Controllers\Mall\CouponController;
use App\Http\Controllers\Mall\IndexController;
use App\Http\Controllers\Mall\OrderController;
use App\Http\Controllers\Mall\ProductController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group([
    'domain' => config('custom.domains.api_domain'),
    'prefix' => 'mall',
], static function (Router $router) {
    // 首页
    $router->get('', [IndexController::class, 'index']);
    // 品牌
    $router->get('brands', [IndexController::class, 'brands']);
    // 轮播图
    $router->get('banners', [IndexController::class, 'banners']);
    // 分类
    $router->get('categories', [CategoryController::class, 'index']);
    // 分类详情
    $router->get('categories/{category}', [CategoryController::class, 'show'])
        ->whereNumber('category');
    // 优惠券
    $router->get('coupons', [CouponController::class, 'index']);
    // 优惠券详情
    $router->get('coupons/{coupon}', [CouponController::class, 'show'])
        ->whereNumber('coupon');
    // 商品
    $router->get('products', [ProductController::class, 'index']);
    // 商品详情
    $router->get('products/{product}', [ProductController::class, 'show'])
        ->whereNumber('product');
    // 购物车
    $router->middleware('auth:sanctum')
        ->prefix('cart')
        ->group(function () use ($router) {
            $router->get('', [CartController::class, 'index']);
            $router->post('add', [CartController::class, 'add']);
            $router->put('items/{item}', [CartController::class, 'update']);
            $router->post('items/{item}/toggle', [CartController::class, 'toggle']);
            $router->delete('items/{item}', [CartController::class, 'remove']);
            $router->post('clear', [CartController::class, 'clear']);
        });
    // 订单
    $router->middleware('auth:sanctum')
        ->group(function () use ($router) {
            $router->get('orders', [OrderController::class, 'index']);
            $router->get('orders/{order}', [OrderController::class, 'show']);
            $router->post('orders', [OrderController::class, 'create']);
            $router->post('orders/{order}/cancel', [OrderController::class, 'cancel']);
            $router->delete('orders/{order}', [OrderController::class, 'destroy']);
        });
});
