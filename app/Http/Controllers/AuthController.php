<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function logout(): JsonResponse
    {
        Auth::user()->currentAccessToken()->delete();

        return $this->success();
    }
}
