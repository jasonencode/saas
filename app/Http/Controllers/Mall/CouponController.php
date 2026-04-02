<?php

namespace App\Http\Controllers\Mall;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mall\CouponResource;
use App\Http\Responses\ApiResponse;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    /**
     * 获取优惠券列表
     */
    public function index(Request $request): JsonResponse
    {
        $coupons = Coupon::ofEnabled()
            ->when($request->filled('type'), function (Builder $builder) use ($request) {
                $builder->where('type', $request->type);
            })
            ->when($request->filled('min_amount'), function (Builder $builder) use ($request) {
                $builder->where('threshold_amount', '>=', $request->min_amount);
            })
            ->when($request->filled('max_amount'), function (Builder $builder) use ($request) {
                $builder->where('threshold_amount', '<=', $request->max_amount);
            })
            ->latest()
            ->paginate((int) $request->input('limit', 20));

        return ApiResponse::success(CouponResource::collection($coupons));
    }

    /**
     * 获取优惠券详情
     */
    public function show(Coupon $coupon): JsonResponse
    {
        if (! $coupon->status || ! $coupon->canBeUsed()) {
            return ApiResponse::notFound('优惠券不存在或已失效');
        }

        return ApiResponse::success(new CouponResource($coupon));
    }
}
