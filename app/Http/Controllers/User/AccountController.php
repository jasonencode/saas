<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
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

    public function logs(): JsonResponse
    {
        $logs = Auth::user()->account->logs()->latest()
            ->paginate(min(request()->integer('per_page', config('custom.pagination.default_per_page')), config('custom.pagination.max_per_page')));

        return ApiResponse::success($logs);
    }
}
