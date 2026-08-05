<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Resources\Finance\VoucherResource;
use App\Http\Responses\ApiResponse;
use App\Models\Finance\Voucher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VoucherController extends Controller
{
    /**
     * 获取结算凭据列表
     *
     * @param  Request  $request  请求
     *
     * @return JsonResponse 结算凭据列表
     */
    public function index(Request $request): JsonResponse
    {
        $vouchers = Voucher::where('user_id', Auth::id())
            ->with('plan')
            ->latest()
            ->paginate(min($request->integer('per_page', config('custom.pagination.default_per_page')), config('custom.pagination.max_per_page')));

        return ApiResponse::success(VoucherResource::collection($vouchers));
    }
}
