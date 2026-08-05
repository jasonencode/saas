<?php

use App\Http\Controllers\Chain\AddressController;
use App\Http\Controllers\Chain\CertificateController;
use App\Http\Controllers\Chain\ContractController;
use App\Http\Controllers\Chain\IndexController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

/*
 * 区块链模块 API
 * 前缀: /chain
 * 中间件: auth:sanctum (全模块需登录)
 */
Route::group([
    'domain' => config('custom.domains.api_domain'),
    'prefix' => 'chain',
    'middleware' => ['auth:sanctum'],
], static function (Router $router) {
    // ---- 网络 ----

    // 支持的区块链网络列表
    $router->get('networks', [IndexController::class, 'networks']);

    // ---- 智能合约 ----

    // 智能合约列表
    $router->get('contracts', [ContractController::class, 'index']);
    // 智能合约详情
    $router->get('contracts/{contract}', [ContractController::class, 'show'])
        ->whereNumber('contract');

    // ---- 证书 ----

    // 证书列表
    $router->get('certificates', [CertificateController::class, 'index']);
    // 创建/铸造证书
    $router->post('certificates', [CertificateController::class, 'create']);
    // 证书详情
    $router->get('certificates/{certificate}', [CertificateController::class, 'show'])
        ->whereNumber('certificate');

    // ---- 区块链地址 ----

    // 当前用户的区块链地址列表
    $router->get('addresses', [AddressController::class, 'index']);
});
