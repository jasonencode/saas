<?php

namespace App\Http\Controllers\Redpack;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Campaign\Redpack;
use Illuminate\Http\JsonResponse;

class IndexController extends Controller
{
    public function index(): JsonResponse
    {
        return ApiResponse::success();
    }

    public function show(Redpack $redpack): JsonResponse
    {
        return ApiResponse::success($redpack);
    }
}
