<?php

namespace App\Http\Controllers\Campaign;

use App\Http\Controllers\Controller;
use App\Http\Requests\Campaign\CouponAvailableRequest;
use App\Http\Requests\Campaign\CouponIndexRequest;
use App\Http\Requests\Campaign\CouponMineRequest;
use App\Http\Resources\Campaign\CouponAvailableResource;
use App\Http\Resources\Campaign\CouponResource;
use App\Http\Resources\Campaign\CouponUserResource;
use App\Http\Responses\ApiResponse;
use App\Models\Campaign\Coupon;
use App\Models\Campaign\CouponUser;
use App\Models\Mall\Sku;
use App\Services\Campaign\CouponService;
use App\Services\Mall\DTOs\OrderItemDto;
use App\Services\Mall\ProductDiscountService;
use App\Support\TenantResolver\TenantResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class CouponController extends Controller
{
    /**
     * 当前登录用户的授权租户 ID 集合（请求内缓存）
     *
     * @var Collection<int, int>|null
     */
    private ?Collection $authorizedTenantIds = null;

    public function __construct(
        protected CouponService $couponService,
    ) {}

    /**
     * 获取优惠券列表
     *
     * @param  CouponIndexRequest  $request  请求
     *
     * @return JsonResponse 优惠券列表
     */
    public function index(CouponIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $coupons = Coupon::ofEnabled()
            ->whereIn('tenant_id', $this->visibleTenantIds($request))
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
            // 已登录时预取当前用户的持有记录，避免列表逐行查询（CAMP-09）
            ->when($user, fn (Builder $builder) => $builder->with([
                'users' => fn (BelongsToMany $query) => $query->wherePivot('user_id', $user->getKey()),
            ]))
            ->latest()
            ->paginate(min((int) ($validated['limit'] ?? config('custom.pagination.default_per_page')), config('custom.pagination.max_per_page')));

        return ApiResponse::success(CouponResource::collection($coupons));
    }

    /**
     * 获取优惠券详情
     *
     * @param  Coupon  $coupon  优惠券
     *
     * @return JsonResponse 优惠券详情
     */
    public function show(Coupon $coupon, Request $request): JsonResponse
    {
        if (!$this->couponIsVisible($coupon, $request)) {
            return ApiResponse::notFound('优惠券不存在或已失效');
        }

        return ApiResponse::success(CouponResource::make($coupon));
    }

    /**
     * 领取优惠券
     *
     * @param  Request  $request  请求
     * @param  Coupon  $coupon  优惠券
     *
     * @throws \Throwable
     *
     * @return JsonResponse 领取结果
     */
    public function claim(Request $request, Coupon $coupon): JsonResponse
    {
        if (!$this->couponIsVisible($coupon, $request)) {
            return ApiResponse::notFound('优惠券不存在或已失效');
        }

        try {
            $this->couponService->sendToUser($coupon, $request->user());
        } catch (InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), 1, null, 422);
        }

        $couponUser = CouponUser::with('coupon')
            ->where('coupon_id', $coupon->getKey())
            ->where('user_id', $request->user()->getKey())
            ->latest('id')
            ->first();

        return ApiResponse::success([
            'message' => '优惠券领取成功',
            'coupon' => CouponResource::make($coupon),
            'user_coupon' => CouponUserResource::make($couponUser),
        ]);
    }

    /**
     * 我的优惠券
     *
     * @param  CouponMineRequest  $request  请求
     *
     * @return JsonResponse 我的优惠券列表
     */
    public function mine(CouponMineRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $tenantIds = $this->effectiveTenantIds($request);

        $coupons = CouponUser::with(['coupon.users' => fn (BelongsToMany $query) => $query->wherePivot('user_id', $request->user()->getKey())])
            ->where('user_id', $request->user()->getKey())
            ->when($tenantIds, fn (Builder $builder) => $builder->whereHas('coupon', fn (Builder $builder) => $builder->whereIn('tenant_id', $tenantIds)))
            ->when(array_key_exists('is_used', $validated), function (Builder $builder) use ($validated) {
                $builder->where('is_used', $validated['is_used']);
            })
            ->latest('id')
            ->paginate(min((int) ($validated['limit'] ?? config('custom.pagination.default_per_page')), config('custom.pagination.max_per_page')));

        return ApiResponse::success(CouponUserResource::collection($coupons));
    }

    /**
     * 优惠券数量统计
     *
     * @param  Request  $request  请求
     *
     * @return JsonResponse 优惠券数量统计
     */
    public function stats(Request $request): JsonResponse
    {
        $userId = $request->user()->getKey();
        $tenantIds = $this->effectiveTenantIds($request);

        $query = CouponUser::where('user_id', $userId)
            ->when($tenantIds, fn (Builder $builder) => $builder->whereHas('coupon', fn (Builder $builder) => $builder->whereIn('tenant_id', $tenantIds)));

        $available = (clone $query)
            ->where('is_used', false)
            ->where(function (Builder $builder) {
                $builder->whereNull('expired_at')->orWhere('expired_at', '>', now());
            })
            ->count();

        $used = (clone $query)->where('is_used', true)->count();

        $expired = (clone $query)
            ->where('is_used', false)
            ->whereNotNull('expired_at')
            ->where('expired_at', '<=', now())
            ->count();

        return ApiResponse::success([
            'available' => $available,
            'used' => $used,
            'expired' => $expired,
        ]);
    }

    /**
     * 获取结算可用券（按租户分组，含预估抵扣）
     *
     * 入参为待结算商品（sku_id + qty），仅跳过库存/可售校验，
     * 单价仍与下单同源（身份折扣），保证「预览可用的券下单必成功且金额一致」。
     *
     * 租户口径与 mine/stats 统一：券须属于「本次请求生效租户集合」
     * （带 X-Tenant-Id 时收窄至该租户，未带时取用户全部授权租户）。
     *
     * @param  CouponAvailableRequest  $request  可用券请求
     *
     * @return JsonResponse 按租户分组的可用券列表
     */
    public function available(CouponAvailableRequest $request): JsonResponse
    {
        $user = $request->user();
        $rows = collect($request->validated('items'));

        $skus = Sku::with('product')
            ->whereIn('id', $rows->pluck('sku_id')->unique()->all())
            ->get()
            ->keyBy('id');

        // 批量取身份折扣（防 N+1），与下单取价同源
        $discountService = service(ProductDiscountService::class);
        $percentMap = $discountService->percentForProducts(
            $user,
            $skus->pluck('product')->filter()->unique('id')->values()
        );

        // 轻量构造订单项并按租户分组：跨店购物车每组独立判定
        $itemsByTenant = [];

        foreach ($rows as $row) {
            $sku = $skus->get((int) $row['sku_id']);

            if (!$sku || !$sku->product) {
                continue;
            }

            $price = isset($percentMap[$sku->product_id])
                ? $discountService->applyPercent($sku->getOrderablePrice(), $percentMap[$sku->product_id])
                : null;

            $itemsByTenant[$sku->product->tenant_id][] = OrderItemDto::forPreview($sku, (int) $row['qty'], $price);
        }

        // 用户持有、未使用、未过期且券定义有效的券实例
        // 租户口径与 mine/stats 统一：带 X-Tenant-Id 时收窄至该租户，未带时不限制
        $tenantIds = $this->effectiveTenantIds($request);

        $couponUsers = CouponUser::with('coupon.products')
            ->where('user_id', $user->getKey())
            ->where('is_used', false)
            ->where(function (Builder $builder) {
                $builder->whereNull('expired_at')->orWhere('expired_at', '>', now());
            })
            ->when($tenantIds, fn (Builder $builder) => $builder->whereHas('coupon', fn (Builder $builder) => $builder->whereIn('tenant_id', $tenantIds)))
            ->latest('id')
            ->get()
            ->filter(fn (CouponUser $couponUser) => $couponUser->coupon?->isValid());

        $groups = [];

        foreach ($couponUsers as $couponUser) {
            $tenantId = (int) $couponUser->coupon->tenant_id;
            $items = collect($itemsByTenant[$tenantId] ?? []);

            $discount = null;
            $baseAmount = null;
            $reason = null;

            try {
                $preview = $this->couponService->previewDiscount($couponUser, $user, $items);
                $discount = $preview['discount'];
                $baseAmount = $preview['base_amount'];
            } catch (InvalidArgumentException $exception) {
                // 不可用券同样返回，供前端置灰展示
                $reason = $exception->getMessage();
            }

            $groups[$tenantId][] = CouponAvailableResource::make(collect([
                'coupon_user' => $couponUser,
                'applicable' => $reason === null,
                'discount_preview' => $discount,
                'base_amount' => $baseAmount,
                'inapplicable_reason' => $reason,
            ]));
        }

        $data = collect($groups)
            ->map(fn (array $coupons, int $tenantId) => [
                'tenant_id' => $tenantId,
                'coupons' => $coupons,
            ])
            ->values();

        return ApiResponse::success($data);
    }

    /**
     * 判断优惠券是否可见
     *
     * 准入口径：租户头有效 + 券属于该租户，不依赖 user_tenant 授权关系（CAMP-05）；
     * 未带租户头时已登录用户按授权租户判定，匿名请求一律不可见。
     *
     * @param  Coupon  $coupon  优惠券
     * @param  Request  $request  请求
     *
     * @return bool 是否可见
     */
    protected function couponIsVisible(Coupon $coupon, Request $request): bool
    {
        if (!$coupon->status || !$coupon->canBeUsed()) {
            return false;
        }

        $tenant = TenantResolver::current();

        if ($tenant) {
            return (int) $coupon->tenant_id === $tenant->getKey();
        }

        // 未带租户头：已登录用户按授权租户判定；匿名一律不可见
        return $this->tenantIdsFor($request)->contains((int) $coupon->tenant_id);
    }

    /**
     * 获取当前请求可见的租户 ID 集合
     *
     * 带 X-Tenant-Id 时收窄至该租户；未带时已登录用户取授权租户，匿名为空集（不返回全平台）。
     *
     * @param  Request  $request  请求
     *
     * @return Collection<int, int> 租户 ID 集合
     */
    protected function visibleTenantIds(Request $request): Collection
    {
        $tenant = TenantResolver::current();

        if ($tenant) {
            return collect([$tenant->getKey()]);
        }

        return $this->tenantIdsFor($request);
    }

    /**
     * 获取当前登录用户的授权租户 ID 集合
     *
     * @param  Request  $request  请求
     *
     * @return Collection<int, int> 租户 ID 集合
     */
    protected function tenantIdsFor(Request $request): Collection
    {
        if ($this->authorizedTenantIds !== null) {
            return $this->authorizedTenantIds;
        }

        $user = $request->user();

        if (!$user) {
            return $this->authorizedTenantIds = collect();
        }

        return $this->authorizedTenantIds = $user->tenants()->pluck('tenants.id');
    }

    /**
     * 获取本次请求生效的租户 ID 集合
     *
     * 带 X-Tenant-Id 时收窄至该租户；未带时返回 null 表示不限制
     * （用户自己的券不再要求 user_tenant 授权，CAMP-05）。
     *
     * @param  Request  $request  请求
     *
     * @return Collection<int, int>|null 租户 ID 集合
     */
    protected function effectiveTenantIds(Request $request): ?Collection
    {
        $tenant = TenantResolver::current();

        return $tenant ? collect([$tenant->getKey()]) : null;
    }
}
