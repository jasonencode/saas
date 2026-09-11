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
        $tenantId = $this->currentTenantId();

        $coupons = Coupon::ofEnabled()
            ->when($tenantId, function (Builder $builder) use ($tenantId) {
                $builder->where('tenant_id', $tenantId);
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

        $coupons = CouponUser::with('coupon')
            ->where('user_id', $request->user()->getKey())
            ->whereHas('coupon', function (Builder $builder) use ($request) {
                $builder->whereIn('tenant_id', $this->effectiveTenantIds($request));
            })
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
            ->whereHas('coupon', function (Builder $builder) use ($tenantIds) {
                $builder->whereIn('tenant_id', $tenantIds);
            });

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

        $skus = Sku::query()
            ->with('product')
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
        // 租户口径与 mine/stats 统一：仅返回「本次请求生效租户集合」内的券
        $tenantIds = $this->effectiveTenantIds($request);

        $couponUsers = CouponUser::query()
            ->with('coupon')
            ->where('user_id', $user->getKey())
            ->where('is_used', false)
            ->where(function (Builder $builder) {
                $builder->whereNull('expired_at')->orWhere('expired_at', '>', now());
            })
            ->whereHas('coupon', function (Builder $builder) use ($tenantIds) {
                $builder->whereIn('tenant_id', $tenantIds);
            })
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
     * 租户口径：
     *  - 带 X-Tenant-Id：券必须属于该租户；已登录时该租户还须在用户授权租户内（防伪造租户头）
     *  - 未带 X-Tenant-Id：已登录用户按其授权租户集合判定；匿名请求不做租户限制
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
        $user = $request->user();

        // 带租户头：租户必须存在（TenantResolver 已校验）且已登录用户须有该租户授权
        if ($tenant) {
            if ($user && !$this->tenantIdsFor($request)->contains($tenant->getKey())) {
                return false;
            }

            return (int) $coupon->tenant_id === $tenant->getKey();
        }

        // 未带租户头：已登录用户按授权租户集合判定
        if ($user) {
            return $this->tenantIdsFor($request)->contains((int) $coupon->tenant_id);
        }

        return true;
    }

    /**
     * 获取当前请求租户 ID（来自 X-Tenant-Id 请求头）
     *
     * @return int|null 租户 ID
     */
    protected function currentTenantId(): ?int
    {
        return TenantResolver::current()?->getKey();
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
     * 带 X-Tenant-Id 时收窄至该租户（未授权则为空集），未带时取用户全部授权租户。
     *
     * @param  Request  $request  请求
     *
     * @return Collection<int, int> 租户 ID 集合
     */
    protected function effectiveTenantIds(Request $request): Collection
    {
        $tenantIds = $this->tenantIdsFor($request);
        $tenant = TenantResolver::current();

        return $tenant ? $tenantIds->intersect([$tenant->getKey()]) : $tenantIds;
    }
}
