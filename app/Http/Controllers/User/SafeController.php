<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\SetPaymentPasswordRequest;
use App\Http\Requests\User\UpdatePasswordRequest;
use App\Http\Resources\User\LoginRecordCollection;
use App\Http\Responses\ApiResponse;
use App\Services\Finance\UserAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SafeController extends Controller
{
    /**
     * 修改密码
     *
     * @param  UpdatePasswordRequest  $request  修改密码请求
     *
     * @return JsonResponse 操作结果
     */
    public function password(UpdatePasswordRequest $request): JsonResponse
    {
        Auth::user()->update([
            'password' => $request->safe()->string('new_pass'),
        ]);

        return ApiResponse::noContent('密码修改成功');
    }

    /**
     * 获取登录记录
     *
     * @return JsonResponse 登录记录列表
     */
    public function records(): JsonResponse
    {
        $list = Auth::user()->records()->latest()
            ->paginate(min(request()->integer('per_page', config('custom.pagination.default_per_page')), config('custom.pagination.max_per_page')));

        return ApiResponse::success(new LoginRecordCollection($list));
    }

    /**
     * 退出登录
     *
     * @return JsonResponse 操作结果
     */
    public function logout(): JsonResponse
    {
        Auth::user()->currentAccessToken()?->delete();

        return ApiResponse::noContent('已退出登录');
    }

    /**
     * 设置支付密码
     *
     * @param  SetPaymentPasswordRequest  $request  设置请求
     *
     * @return JsonResponse 操作结果
     */
    public function setPaymentPassword(SetPaymentPasswordRequest $request): JsonResponse
    {
        $account = Auth::user()->account;

        if (!$account) {
            return ApiResponse::error('用户账户不存在');
        }

        $service = service(UserAccountService::class);
        $service->setPaymentPassword($account, $request->validated('password'));

        return ApiResponse::noContent('支付密码设置成功');
    }

    /**
     * 修改支付密码
     *
     * @param  SetPaymentPasswordRequest  $request  修改请求
     *
     * @return JsonResponse 操作结果
     */
    public function changePaymentPassword(SetPaymentPasswordRequest $request): JsonResponse
    {
        $account = Auth::user()->account;

        if (!$account) {
            return ApiResponse::error('用户账户不存在');
        }

        $service = service(UserAccountService::class);
        $service->changePaymentPassword(
            $account,
            $request->validated('old_password'),
            $request->validated('password')
        );

        return ApiResponse::noContent('支付密码修改成功');
    }
}
