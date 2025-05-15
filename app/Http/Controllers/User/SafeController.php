<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Resources\RecordCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SafeController extends Controller
{
    /**
     * 修改密码
     *
     * @param  UpdatePasswordRequest  $request
     * @return JsonResponse
     */
    public function password(UpdatePasswordRequest $request)
    {
        Auth::user()->update([
            'password' => $request->post('new_pass'),
        ]);

        return $this->success();
    }

    public function records(Request $request)
    {
        $list = Auth::user()->records()->latest()->paginate();

        return $this->success(new RecordCollection($list));
    }
}
