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

        $tenant = TenantResolver::current();

        $redpacks = Redpack::query()
            ->ofEnabled()
            ->when($tenant, fn (Builder $builder) => $builder->where('tenant_id', $tenant->getKey()))
            ->when(!$tenant && $request->user(), function (Builder $builder) use ($request) {
                $builder->whereIn('tenant_id', $this->tenantIdsFor($request));
            })
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
            ->whereHas('redpack', fn (Builder $builder) => $builder->whereIn('tenant_id', $tenantIds))
            ->latest('claimed_at')
            ->paginate(min((int) ($validated['limit'] ?? config('custom.pagination.default_per_page')), config('custom.pagination.max_per_page')));

        return ApiResponse::success(RedpackCodeResource::collection($codes));
    }

    /**
     * 判断红包活动对当前请求是否可见
     *
     * - 带 X-Tenant-Id：必须属于该租户；已登录用户还需有该租户授权
     * - 未带 X-Tenant-Id：已登录用户按其授权租户判定；匿名请求不做租户限制
     */
    private function redpackIsVisible(?Redpack $redpack, Request $request): bool
    {
        if ($redpack === null || !$redpack->isEnabled()) {
            return false;
        }

        $tenant = TenantResolver::current();
        $user = $request->user();

        if ($tenant) {
            if ($user && !$this->tenantIdsFor($request)->contains($tenant->getKey())) {
                return false;
            }

            return (int) $redpack->tenant_id === $tenant->getKey();
        }

        if ($user) {
            return $this->tenantIdsFor($request)->contains((int) $redpack->tenant_id);
        }

        return true;
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
     * @return Collection<int, int>
     */
    private function effectiveTenantIds(Request $request): Collection
    {
        $tenantIds = $this->tenantIdsFor($request);
        $tenant = TenantResolver::current();

        return $tenant ? $tenantIds->intersect([$tenant->getKey()]) : $tenantIds;
    }
}
