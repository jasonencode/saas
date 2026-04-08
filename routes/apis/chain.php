<?php

use App\Http\Controllers\Chain\AddressController;
use App\Http\Controllers\Chain\CertificateController;
use App\Http\Controllers\Chain\ContractController;
use App\Http\Controllers\Chain\IndexController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group([
    'domain' => config('custom.domains.api_domain'),
    'prefix' => 'chain',
], static function (Router $router) {
    $router->get('networks', [IndexController::class, 'networks']);

    // 智能合约
    $router->get('contracts', [ContractController::class, 'index']);
    $router->get('contracts/{contract}', [ContractController::class, 'show'])
        ->whereNumber('contract');
    // 证书
    $router->get('certificates', [CertificateController::class, 'index']);
    $router->post('certificates', [CertificateController::class, 'create']);
    $router->get('certificates/{certificate}', [CertificateController::class, 'show'])
        ->whereNumber('certificate');
    // 区块链地址
    $router->get('addresses', [AddressController::class, 'index']);
});
