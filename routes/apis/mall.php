<?php

use App\Http\Controllers\Mall\CartController;
use App\Http\Controllers\Mall\CategoryController;
use App\Http\Controllers\Mall\ExpressController;
use App\Http\Controllers\Mall\IndexController;
use App\Http\Controllers\Mall\OrderController;
use App\Http\Controllers\Mall\ProductController;
use App\Http\Controllers\Mall\RefundController;
use App\Http\Controllers\Mall\TagController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

/*
 * 商城模块 API
 * 前缀: /mall
 * 中间件: store.opened (店铺已开通校验)
 */
Route::group([
    'domain' => config('custom.domains.api_domain'),
    'prefix' => 'mall',
    'middleware' => 'store.opened',
], static function (Router $router) {
    // ---- 商城首页 ----

    // 商城首页数据 (聚合接口)
    $router->get('', [IndexController::class, 'index']);
    // 品牌列表
    $router->get('brands', [IndexController::class, 'brands']);
    // 首页轮播图
    $router->get('banners', [IndexController::class, 'banners']);

    // ---- 商品分类 ----

    // 分类列表 (树形结构)
    $router->get('categories', [CategoryController::class, 'index']);
    // 分类详情 (含下级分类及商品)
    $router->get('categories/{category}', [CategoryController::class, 'show'])
        ->whereNumber('category');

    // ---- 标签 ----

    // 商品标签列表 (按使用量排序)
    $router->get('tags', [TagController::class, 'index']);

    // ---- 商品 ----

    // 商品列表 (支持筛选、排序、分页)
    $router->get('products', [ProductController::class, 'index']);
    // 商品详情 (含 SKU、规格等完整信息)
    $router->get('products/{product}', [ProductController::class, 'show'])
        ->whereNumber('product');

    // ---- 购物车 (需登录) ----

    $router->middleware('auth:sanctum')
        ->prefix('cart')
        ->group(function () use ($router) {
            // 购物车列表
            $router->get('', [CartController::class, 'index']);
            // 添加商品到购物车
            $router->post('add', [CartController::class, 'add']);
            // 购物车结算预览 (计算优惠、运费等)
            $router->post('preview', [CartController::class, 'preview']);
            // 从购物车下单结算
            $router->post('checkout', [CartController::class, 'createFromCart']);
            // 更新购物车商品数量
            $router->put('items/{item}', [CartController::class, 'update']);
            // 移除购物车商品
            $router->delete('items/{item}', [CartController::class, 'remove']);
            // 清空购物车
            $router->post('clear', [CartController::class, 'clear']);
        });

    // ---- 物流公司 ----

    // 物流公司列表 (退货物流选择)
    $router->get('expresses', [ExpressController::class, 'index']);

    // ---- 订单 (需登录) ----

    $router->middleware('auth:sanctum')
        ->group(function () use ($router) {
            // 订单列表 (支持按状态筛选)
            $router->get('orders', [OrderController::class, 'index']);
            // 订单详情 (含商品明细、物流等)
            $router->get('orders/{order}', [OrderController::class, 'show']);
            // 创建订单
            $router->post('orders', [OrderController::class, 'create']);
            // 取消订单
            $router->post('orders/{order}/cancel', [OrderController::class, 'cancel']);
            // 确认收货
            $router->post('orders/{order}/sign', [OrderController::class, 'sign']);
            // 删除订单
            $router->delete('orders/{order}', [OrderController::class, 'destroy']);
        });

    // ---- 退款/售后 (需登录) ----

    $router->middleware('auth:sanctum')
        ->group(function () use ($router) {
            // 申请退款
            $router->post('orders/{order}/refund', [RefundController::class, 'store']);
            // 退款列表 (支持按状态筛选)
            $router->get('refunds', [RefundController::class, 'index']);
            // 退款详情 (含商品明细、物流、日志)
            $router->get('refunds/{refund}', [RefundController::class, 'show']);
            // 取消退款
            $router->post('refunds/{refund}/cancel', [RefundController::class, 'cancel']);
            // 提交退货物流
            $router->post('refunds/{refund}/ship', [RefundController::class, 'ship']);
        });
});
