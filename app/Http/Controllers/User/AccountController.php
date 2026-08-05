<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\Finance\UserAccountLogCollection;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    /**
     * 获取账户余额
     *
     * @return JsonResponse 账户余额信息
     */
    public function index(): JsonResponse
    {
        $account = Auth::user()->account;

        return ApiResponse::success([
            'balance' => $account->balance,
            'frozen_balance' => $account->frozen_balance,
            'points' => $account->points,
            'frozen_points' => $account->frozen_points,
        ]);
    }

    /**
     * 获取账户流水记录
     *
     * @return JsonResponse 账户流水列表
     */
    public function logs(): JsonResponse
    {
        $logs = Auth::user()->account->logs()->latest()
            ->paginate(min(request()->integer('per_page', config('custom.pagination.default_per_page')), config('custom.pagination.max_per_page')));

        return ApiResponse::success(new UserAccountLogCollection($logs));
    }
}
