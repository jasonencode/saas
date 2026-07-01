<?php

namespace App\Http\Controllers\Campaign;

use App\Http\Controllers\Controller;
use App\Http\Resources\Campaign\RedpackCodeResource;
use App\Http\Resources\Campaign\RedpackResource;
use App\Http\Responses\ApiResponse;
use App\Models\Campaign\Redpack;
use App\Models\Campaign\RedpackCode;
use App\Services\Campaign\RedpackService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class RedpackController extends Controller
{
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

        $redpacks = Redpack::ofTenant()
            ->when($request->filled('name'), function (Builder $builder) use ($validated) {
                $builder->where('name', 'like', "%{$validated['name']}%");
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
    public function show(Redpack $redpack): JsonResponse
    {
        if (!$redpack->isEnabled()) {
            return ApiResponse::notFound('红包活动不存在或已禁用');
        }

        return ApiResponse::success(new RedpackResource($redpack));
    }

    /**
     * 领取红包
     */
    public function claim(Request $request, string $code): JsonResponse
    {
        $codeModel = RedpackCode::where('code', $code)->first();

        if (!$codeModel) {
            return ApiResponse::notFound('红包码不存在');
        }

        try {
            $this->redpackService->claim($codeModel, $request->user(), $request->ip());
        } catch (InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), 1, null, 422);
        }

        return ApiResponse::success([
            'amount' => $codeModel->amount,
            'claimed_at' => $codeModel->claimed_at->toDateTimeString(),
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

        $codes = RedpackCode::query()
            ->with('redpack')
            ->where('user_id', $request->user()->getKey())
            ->latest('claimed_at')
            ->paginate(min((int) ($validated['limit'] ?? config('custom.pagination.default_per_page')), config('custom.pagination.max_per_page')));

        return ApiResponse::success(RedpackCodeResource::collection($codes));
    }
}
