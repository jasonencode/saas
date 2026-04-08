<?php

use App\Http\Controllers\AppVersionController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group([
    'domain' => config('custom.domains.api_domain'),
], static function (Router $router) {
    $router->get('/', fn () => 'Server is working');
    $router->get('app_version', [AppVersionController::class, 'index']);
});
