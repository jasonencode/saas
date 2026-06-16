<?php

namespace App\Http\Controllers\Campaign;

use App\Http\Controllers\Controller;
use App\Http\Resources\Campaign\LotteryDrawResource;
use App\Http\Resources\Campaign\LotteryPrizeRecordResource;
use App\Http\Resources\Campaign\LotteryResource;
use App\Http\Responses\ApiResponse;
use App\Models\Campaign\Lottery;
use App\Services\Campaign\LotteryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class LotteryController extends Controller
{
    public function __construct(
        protected LotteryService $lotteryService,
    ) {}

    /**
     * 抽奖活动列表
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'status' => ['sometimes', 'boolean'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $lotteries = Lottery::ofTenant()
            ->when($request->filled('name'), function (Builder $builder) use ($validated) {
                $builder->where('name', 'like', "%{$validated['name']}%");
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
     */
    public function show(Lottery $lottery): JsonResponse
    {
        if (! $lottery->isEnabled()) {
            return ApiResponse::notFound('活动不存在或已禁用');
        }

        $lottery->load('prizes');

        return ApiResponse::success(new LotteryResource($lottery));
    }

    /**
     * 抽奖
     */
    public function draw(Request $request, Lottery $lottery): JsonResponse
    {
        try {
            $draw = $this->lotteryService->draw(
                $lottery,
                $request->user(),
                $request->ip(),
                $request->userAgent(),
            );
        } catch (InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), 1, null, 422);
        }

        return ApiResponse::success(new LotteryDrawResource($draw), '抽奖成功');
    }

    /**
     * 我的抽奖记录
     */
    public function myDraws(Request $request, Lottery $lottery): JsonResponse
    {
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
     */
    public function myPrizes(Request $request, Lottery $lottery): JsonResponse
    {
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
     */
    public function availableDraws(Request $request, Lottery $lottery): JsonResponse
    {
        $available = $this->lotteryService->getAvailableDraws($lottery, $request->user());

        return ApiResponse::success([
            'available_draws' => $available,
        ]);
    }
}
