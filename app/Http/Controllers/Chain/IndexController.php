<?php

namespace App\Http\Controllers\Chain;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class IndexController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->success();
    }

    public function networks(): JsonResponse
    {
        return $this->success();
    }
}