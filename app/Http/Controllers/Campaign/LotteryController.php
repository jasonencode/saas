<?php

namespace App\Http\Controllers\Campaign;

use App\Http\Controllers\Controller;
use App\Http\Resources\Campaign\LotteryDrawResource;
use App\Http\Resources\Campaign\LotteryPrizeRecordResource;
use App\Http\Resources\Campaign\LotteryResource;
use App\Http\Responses\ApiResponse;
use App\Models\Campaign\Lottery;
use App\Services\Campaign\LotteryService;
use App\Support\TenantResolver\TenantResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class LotteryController extends Controller
{
    /**
     * 当前登录用户的授权租户 ID 集合
     *
     * @var Collection<int, int>|null
     */
    private ?Collection $authorizedTenantIds = null;

    public function __construct(
        protected LotteryService $lotteryService,
    ) {}

    /**
     * 抽奖活动列表
     *
     * @param  Request  $request  请求
     *
     * @return JsonResponse 抽奖活动列表
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'status' => ['sometimes', 'boolean'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $tenantIds = $this->visibleTenantIds($request);

        $lotteries = Lottery::query()
            ->ofEnabled()
            ->when($tenantIds !== null, function (Builder $builder) use ($tenantIds) {
                // 平台级活动（tenant_id 为空）对所有租户可见
                $builder->where(function (Builder $builder) use ($tenantIds) {
                    $builder->whereNull('tenant_id')
                        ->orWhereIn('tenant_id', $tenantIds);
                });
            })
            ->when($request->filled('name'), function (Builder $builder) use ($validated) {
                $builder->search('name', $validated['name']);
            })
            ->when(array_key_exists('status', $validated), function (Builder $builder) use ($validated) {
                $builder->where('status', $validated['status']);
            })
            ->withCount('prizes')
            ->latest()
            ->paginate(min((int) ($validated['limit'] ?? config('custom.pagination.default_per_page')), config('custom.pagination.max_per_page')));

        return ApiResponse::success(LotteryResource::collection($lotteries));
    }

    /**
     * 抽奖活动详情
     *
     * @param  Request  $request  请求
     * @param  Lottery  $lottery  抽奖活动
     *
     * @return JsonResponse 抽奖活动详情
     */
    public function show(Request $request, Lottery $lottery): JsonResponse
    {
        if (!$this->lotteryIsVisible($lottery, $request)) {
            return ApiResponse::notFound('活动不存在或已禁用');
        }

        $lottery->load('prizes');

        return ApiResponse::success(LotteryResource::make($lottery));
    }

    /**
     * 抽奖
     *
     * @param  Request  $request  请求
     * @param  Lottery  $lottery  抽奖活动
     *
     * @return JsonResponse 抽奖结果
     */
    public function draw(Request $request, Lottery $lottery): JsonResponse
    {
        if (!$this->lotteryIsVisible($lottery, $request)) {
            return ApiResponse::notFound('活动不存在或已禁用');
        }

        $draw = $this->lotteryService->draw(
            $lottery,
            $request->user(),
            $request->ip(),
            $request->userAgent(),
        );

        return ApiResponse::success(LotteryDrawResource::make($draw), '抽奖成功');
    }

    /**
     * 我的抽奖记录
     *
     * @param  Request  $request  请求
     * @param  Lottery  $lottery  抽奖活动
     *
     * @return JsonResponse 我的抽奖记录列表
     */
    public function myDraws(Request $request, Lottery $lottery): JsonResponse
    {
        if (!$this->lotteryIsVisible($lottery, $request)) {
            return ApiResponse::notFound('活动不存在或已禁用');
        }

        $validated = $request->validate([
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $draws = $lottery->draws()
            ->with('prize')
            ->where('user_id', $request->user()->getKey())
            ->latest()
            ->paginate(min((int) ($validated['limit'] ?? config('custom.pagination.default_per_page')), config('custom.pagination.max_per_page')));

        return ApiResponse::success(LotteryDrawResource::collection($draws));
    }

    /**
     * 我的中奖记录
     *
     * @param  Request  $request  请求
     * @param  Lottery  $lottery  抽奖活动
     *
     * @return JsonResponse 我的中奖记录列表
     */
    public function myPrizes(Request $request, Lottery $lottery): JsonResponse
    {
        if (!$this->lotteryIsVisible($lottery, $request)) {
            return ApiResponse::notFound('活动不存在或已禁用');
        }

        $validated = $request->validate([
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $records = $lottery->prizeRecords()
            ->with('prize')
            ->where('user_id', $request->user()->getKey())
            ->latest()
            ->paginate(min((int) ($validated['limit'] ?? config('custom.pagination.default_per_page')), config('custom.pagination.max_per_page')));

        return ApiResponse::success(LotteryPrizeRecordResource::collection($records));
    }

    /**
     * 剩余抽奖次数
     *
     * @param  Request  $request  请求
     * @param  Lottery  $lottery  抽奖活动
     *
     * @return JsonResponse 剩余抽奖次数
     */
    public function availableDraws(Request $request, Lottery $lottery): JsonResponse
    {
        if (!$this->lotteryIsVisible($lottery, $request)) {
            return ApiResponse::notFound('活动不存在或已禁用');
        }

        $available = $this->lotteryService->getAvailableDraws($lottery, $request->user());

        return ApiResponse::success([
            'available_draws' => $available,
        ]);
    }

    /**
     * 判断抽奖活动对当前请求是否可见
     *
     * - 平台级活动（tenant_id 为空）对所有租户可见
     * - 带 X-Tenant-Id：活动须属于该租户；已登录用户还须有该租户授权
     * - 未带 X-Tenant-Id：已登录用户按授权租户判定；匿名请求不做租户限制
     *
     * @param  Lottery  $lottery  抽奖活动
     * @param  Request  $request  请求
     *
     * @return bool 是否可见
     */
    protected function lotteryIsVisible(Lottery $lottery, Request $request): bool
    {
        if (!$lottery->isEnabled()) {
            return false;
        }

        if ($lottery->tenant_id === null) {
            return true;
        }

        $tenant = TenantResolver::current();
        $user = $request->user();

        if ($tenant) {
            if ($user && !$this->tenantIdsFor($request)->contains($tenant->getKey())) {
                return false;
            }

            return (int) $lottery->tenant_id === $tenant->getKey();
        }

        if ($user) {
            return $this->tenantIdsFor($request)->contains((int) $lottery->tenant_id);
        }

        return true;
    }

    /**
     * 获取本次请求可见的租户 ID 集合
     *
     * 返回 null 表示不限制租户（匿名且未带租户头）。
     *
     * @param  Request  $request  请求
     *
     * @return Collection<int, int>|null 租户 ID 集合
     */
    protected function visibleTenantIds(Request $request): ?Collection
    {
        if ($request->user()) {
            return $this->effectiveTenantIds($request);
        }

        $tenant = TenantResolver::current();

        return $tenant ? collect([$tenant->getKey()]) : null;
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

        return $tenant ? $tenantIds->intersect([$tenant->getKey()])->values() : $tenantIds;
    }
}
