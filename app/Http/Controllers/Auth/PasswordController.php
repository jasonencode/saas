<?php

namespace App\Http\Controllers\Auth;

use App\Factories\AuthResponse;
use App\Http\Controllers\AuthController;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;

class PasswordController extends AuthController
{
    public function login(LoginRequest $request)
    {
        $credentials = $request->safe()->only(['username', 'password']);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            return $this->success(new AuthResponse($user));
        } else {
            return $this->error('账号或密码错误', 422);
        }
    }
}