<?php

use App\Http\Controllers\Redpack\IndexController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group([
    'domain' => config('custom.domains.api_domain'),
    'prefix' => 'redpacks',
], static function (Router $router) {
    $router->get('', [IndexController::class, 'index']);
    $router->get('{redpack}', [IndexController::class, 'index'])
        ->whereAlphaNumeric('redpack');
});
