<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserInfoRequest;
use App\Http\Resources\UserInfoResource;
use Illuminate\Support\Facades\Auth;

class InfoController extends Controller
{
    public function index()
    {
        return $this->success(new UserInfoResource(Auth::user()));
    }

    public function update(UpdateUserInfoRequest $request)
    {
        $data = $request->safe()->only(['nickname', 'gender', 'birthday', 'avatar']);
        $data = array_filter($data, function($item) {
            return !blank($item);
        });

        Auth::user()->info->update($data);

        return $this->success(new UserInfoResource(Auth::user()));
    }
}