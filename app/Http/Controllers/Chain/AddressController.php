<?php

namespace App\Http\Controllers\Chain;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class AddressController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->success();
    }
}