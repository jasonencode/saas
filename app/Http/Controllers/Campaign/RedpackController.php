<?php

namespace App\Http\Controllers\Campaign;

use App\Http\Controllers\Controller;
use App\Http\Resources\Campaign\RedpackCodeResource;
use App\Http\Resources\Campaign\RedpackResource;
use App\Http\Responses\ApiResponse;
use App\Models\Campaign\Redpack;
use App\Models\Campaign\RedpackCode;
use App\Services\Campaign\RedpackService;
use App\Services\System\EventLogService;
use App\Support\TenantResolver\TenantResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class RedpackController extends Controller
{
    /**
     * 当前登录用户的授权租户 ID 集合
     *
     * @var Collection<int, int>|null
     */
    private ?Collection $authorizedTenantIds = null;

    public function __construct(
        protected RedpackService $redpackService,
    ) {}

    /**
     * 红包活动列表
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'status' => ['sometimes', 'boolean'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $redpacks = Redpack::query()
            ->ofEnabled()
            ->whereIn('tenant_id', $this->visibleTenantIds($request))
            ->when($request->filled('name'), function (Builder $builder) use ($validated) {
                $builder->search('name', $validated['name']);
            })
            ->when(array_key_exists('status', $validated), function (Builder $builder) use ($validated) {
                $builder->where('status', $validated['status']);
            })
            ->withCount('codes')
            ->latest()
            ->paginate(min((int) ($validated['limit'] ?? config('custom.pagination.default_per_page')), config('custom.pagination.max_per_page')));

        return ApiResponse::success(RedpackResource::collection($redpacks));
    }

    /**
     * 红包活动详情
     */
    public function show(Request $request, Redpack $redpack): JsonResponse
    {
        if (!$this->redpackIsVisible($redpack, $request)) {
            return ApiResponse::notFound('红包活动不存在或已禁用');
        }

        return ApiResponse::success(RedpackResource::make($redpack));
    }

    /**
     * 领取红包
     */
    public function claim(Request $request, string $code): JsonResponse
    {
        $codeModel = RedpackCode::where('code', $code)->first();

        if (!$codeModel || !$this->redpackIsVisible($codeModel->redpack, $request)) {
            return ApiResponse::notFound('红包码不存在');
        }

        $claimed = $this->redpackService->claim($codeModel, $request->user(), $request->ip());

        service(EventLogService::class)->write(
            'redpack.claimed',
            '领取红包',
            $request->user(),
            $claimed,
            payload: [
                'redpack_id' => $claimed->redpack_id,
                'amount' => $claimed->amount,
            ],
        );

        return ApiResponse::success([
            'amount' => $claimed->amount,
            'claimed_at' => $claimed->claimed_at->toDateTimeString(),
        ], '红包领取成功');
    }

    /**
     * 我的红包（已领取的红包列表）
     */
    public function mine(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $tenantIds = $this->effectiveTenantIds($request);

        $codes = RedpackCode::with('redpack')
            ->where('user_id', $request->user()->getKey())
            ->when($tenantIds, fn (Builder $builder) => $builder->whereHas('redpack', fn (Builder $builder) => $builder->whereIn('tenant_id', $tenantIds)))
            ->latest('claimed_at')
            ->paginate(min((int) ($validated['limit'] ?? config('custom.pagination.default_per_page')), config('custom.pagination.max_per_page')));

        return ApiResponse::success(RedpackCodeResource::collection($codes));
    }

    /**
     * 判断红包活动对当前请求是否可见
     *
     * 准入口径：租户头有效 + 活动属于该租户，不依赖 user_tenant 授权关系（CAMP-05）；
     * 未带租户头时已登录用户按授权租户判定，匿名请求一律不可见。
     */
    private function redpackIsVisible(?Redpack $redpack, Request $request): bool
    {
        if ($redpack === null || !$redpack->isEnabled()) {
            return false;
        }

        $tenant = TenantResolver::current();

        if ($tenant) {
            return (int) $redpack->tenant_id === $tenant->getKey();
        }

        // 未带租户头：已登录用户按授权租户判定；匿名一律不可见
        return $this->tenantIdsFor($request)->contains((int) $redpack->tenant_id);
    }

    /**
     * 获取当前请求可见的租户 ID 集合
     *
     * 带 X-Tenant-Id 时收窄至该租户；未带时已登录用户取授权租户，匿名为空集（不返回全平台）。
     *
     * @return Collection<int, int> 租户 ID 集合
     */
    private function visibleTenantIds(Request $request): Collection
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
     * @return Collection<int, int>
     */
    private function tenantIdsFor(Request $request): Collection
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
     * （用户自己的红包记录不再要求 user_tenant 授权，CAMP-05）。
     *
     * @return Collection<int, int>|null 租户 ID 集合
     */
    private function effectiveTenantIds(Request $request): ?Collection
    {
        $tenant = TenantResolver::current();

        return $tenant ? collect([$tenant->getKey()]) : null;
    }
}
