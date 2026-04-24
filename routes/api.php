<?php

use App\Http\Controllers\AppVersionController;
use App\Http\Resources\Contents\ContentCollection;
use App\Http\Resources\Contents\ContentResource;
use App\Http\Responses\ApiResponse;
use App\Models\Content\Content;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group([
    'domain' => config('custom.domains.api_domain'),
], static function (Router $router) {
    $router->get('/', fn () => 'Server is working');
    $router->get('app_version', [AppVersionController::class, 'index']);

    $router->get('demo/list', function () {
        $list = Content::paginate();

        return ApiResponse::success(ContentCollection::make($list));
    });
    $router->get('demo/detail', function () {
        $detail = Content::find(1);

        return ApiResponse::success(ContentResource::make($detail));
    });
});
