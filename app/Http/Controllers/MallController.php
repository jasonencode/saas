<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class MallController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->success();
    }
}
