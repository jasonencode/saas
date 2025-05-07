<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group([
    'domain' => config('custom.domain.default_domain'),
], function(Router $router) {
    $router->get('/', function() {
        return view('welcome');
    });
});

Route::get('MP_verify_{code}.txt', function($code) {
    return $code;
});
