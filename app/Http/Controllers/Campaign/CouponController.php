<?php

namespace App\Http\Controllers\Campaign;

use App\Enums\Campaign\CouponType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Campaign\CouponResource;
use App\Http\Resources\Campaign\CouponUserResource;
use App\Http\Responses\ApiResponse;
use App\Models\Campaign\Coupon;
use App\Models\Campaign\CouponUser;
use App\Services\Campaign\CouponService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class CouponController extends Controller
{
    public function __construct(
        protected CouponService $couponService,
    ) {
    }

    /**
     * 获取优惠券列表
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['sometimes', Rule::enum(CouponType::class)],
            'min_amount' => ['sometimes', 'numeric', 'min:0'],
            'max_amount' => ['sometimes', 'numeric', 'min:0'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $tenant = $request->tenant();

        $coupons = Coupon::ofEnabled()
            ->when($tenant, function (Builder $builder) use ($tenant) {
                $builder->where('tenant_id', $tenant->getKey());
            })
            ->where(function (Builder $builder) {
                $builder
                    ->whereNull('start_at')
                    ->orWhere('start_at', '<=', now());
            })
            ->where(function (Builder $builder) {
                $builder
                    ->whereNull('end_at')
                    ->orWhere('end_at', '>=', now());
            })
            ->when(array_key_exists('type', $validated), function (Builder $builder) use ($validated) {
                $builder->where('type', $validated['type']);
            })
            ->when(array_key_exists('min_amount', $validated), function (Builder $builder) use ($validated) {
                $builder->where('min_amount', '>=', $validated['min_amount']);
            })
            ->when(array_key_exists('max_amount', $validated), function (Builder $builder) use ($validated) {
                $builder->where('min_amount', '<=', $validated['max_amount']);
            })
            ->latest()
            ->paginate((int) ($validated['limit'] ?? 20));

        return ApiResponse::success(CouponResource::collection($coupons));
    }

    /**
     * 获取优惠券详情
     */
    public function show(Coupon $coupon): JsonResponse
    {
        if (!$this->couponIsVisible($coupon)) {
            return ApiResponse::notFound('优惠券不存在或已失效');
        }

        return ApiResponse::success(new CouponResource($coupon));
    }

    /**
     * 领取优惠券
     */
    public function claim(Request $request, Coupon $coupon): JsonResponse
    {
        if (!$this->couponIsVisible($coupon)) {
            return ApiResponse::notFound('优惠券不存在或已失效');
        }

        try {
            $this->couponService->sendToUser($coupon, $request->user());
        } catch (InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), 1, null, 422);
        }

        $couponUser = CouponUser::query()
            ->with('coupon')
            ->where('coupon_id', $coupon->getKey())
            ->where('user_id', $request->user()->getKey())
            ->latest('id')
            ->first();

        return ApiResponse::success([
            'message' => '优惠券领取成功',
            'coupon' => new CouponResource($coupon),
            'user_coupon' => new CouponUserResource($couponUser),
        ]);
    }

    /**
     * 我的优惠券
     */
    public function mine(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'is_used' => ['sometimes', 'boolean'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $tenantId = $this->currentTenantId($request);

        $coupons = CouponUser::query()
            ->with('coupon')
            ->where('user_id', $request->user()->getKey())
            ->whereHas('coupon', function (Builder $builder) use ($tenantId) {
                $builder->when($tenantId, function (Builder $builder) use ($tenantId) {
                    $builder->where('tenant_id', $tenantId);
                });
            })
            ->when(array_key_exists('is_used', $validated), function (Builder $builder) use ($validated) {
                $builder->where('is_used', $validated['is_used']);
            })
            ->latest('id')
            ->paginate((int) ($validated['limit'] ?? 20));

        return ApiResponse::success(CouponUserResource::collection($coupons));
    }

    protected function couponIsVisible(Coupon $coupon): bool
    {
        $tenantId = $this->currentTenantId(request());

        if ($tenantId && (int) $coupon->tenant_id !== $tenantId) {
            return false;
        }

        return (bool) $coupon->status && $coupon->canBeUsed();
    }

    protected function currentTenantId(Request $request): ?int
    {
        if ($request->user()?->tenant_id) {
            return (int) $request->user()->tenant_id;
        }

        $tenant = $request->tenant();
        if ($tenant) {
            return (int) $tenant->getKey();
        }

        return null;
    }
}
