<?php

use App\Http\Controllers\Content\ContentController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group([
    'domain' => config('custom.domains.api_domain'),
], function(Router $router) {
    $router->get('contents', [ContentController::class, 'index']);
});
