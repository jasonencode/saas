<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = User::create([
            'username' => $request->post('username'),
            'password' => $request->post('password'),
        ]);

        return $this->success($user);
    }
}
