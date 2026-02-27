<?php

use App\Http\Controllers\Mall\CategoryController;
use App\Http\Controllers\Mall\CouponController;
use App\Http\Controllers\Mall\IndexController;
use App\Http\Controllers\Mall\ProductController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group([
    'domain' => config('custom.domains.api_domain'),
    'prefix' => 'mall',
], static function (Router $router) {
    # 首页
    $router->get('', [IndexController::class, 'index']);
    # 品牌
    $router->get('brands', [IndexController::class, 'brands']);
    # 轮播图
    $router->get('banners', [IndexController::class, 'banners']);
    # 分类
    $router->get('categories', [CategoryController::class, 'index']);
    # 分类详情
    $router->get('categories/{category}', [CategoryController::class, 'show'])
        ->whereNumber('category');
    # 优惠券
    $router->get('coupons', [CouponController::class, 'index']);
    # 商品
    $router->get('products', [ProductController::class, 'index']);
    # 商品详情
    $router->get('products/{product}', [ProductController::class, 'show'])
        ->whereNumber('product');
});
