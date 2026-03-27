<?php

namespace App\Http\Controllers\Chain;

use App\Http\Controllers\Controller;
use App\Http\Resources\Chain\NetworkResource;
use App\Http\Responses\ApiResponse;
use App\Models\Network;
use Illuminate\Http\JsonResponse;

class IndexController extends Controller
{
    public function index(): JsonResponse
    {
        return ApiResponse::success();
    }

    public function networks(): JsonResponse
    {
        $list = Network::ofEnabled()->get();

        return ApiResponse::success(NetworkResource::make($list));
    }
}
