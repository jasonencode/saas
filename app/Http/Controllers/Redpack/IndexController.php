<?php

namespace App\Http\Controllers\Redpack;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class IndexController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->success();
    }
}