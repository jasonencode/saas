<?php

namespace App\Http\Controllers\Redpack;

use App\Http\Controllers\Controller;
use App\Models\Redpack;
use Illuminate\Http\JsonResponse;

class IndexController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->success();
    }

    public function show(Redpack $redpack): JsonResponse
    {
        return $this->success($redpack);
    }
}