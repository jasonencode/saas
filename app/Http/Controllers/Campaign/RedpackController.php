<?php

namespace App\Http\Controllers\Campaign;

use App\Enums\Campaign\RedpackCodeStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Campaign\RedpackResource;
use App\Http\Responses\ApiResponse;
use App\Models\Campaign\Redpack;
use App\Models\Campaign\RedpackCode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RedpackController extends Controller
{
    /**
     * 红包活动列表
     */
    public function index(Request $request): JsonResponse
    {
        $redpacks = Redpack::ofTenant()
            ->when($request->filled('name'), function (Builder $builder) use ($request) {
                $builder->where('name', 'like', "%{$request->name}%");
            })
            ->when($request->filled('status'), function (Builder $builder) use ($request) {
                $builder->where('status', $request->boolean('status'));
            })
            ->when($request->filled('start_at'), function (Builder $builder) use ($request) {
                $builder->where('start_at', '>=', $request->start_at);
            })
            ->when($request->filled('end_at'), function (Builder $builder) use ($request) {
                $builder->where('end_at', '<=', $request->end_at);
            })
            ->withCount('codes')
            ->latest()
            ->paginate((int) $request->input('limit', 20));

        return ApiResponse::success(RedpackResource::collection($redpacks));
    }

    /**
     * 红包活动详情
     */
    public function show(Redpack $redpack): JsonResponse
    {
        if (!$redpack->status) {
            return ApiResponse::notFound('红包活动不存在或已禁用');
        }

        return ApiResponse::success(new RedpackResource($redpack));
    }

    /**
     * 领取红包
     */
    public function claim(Request $request, string $code): JsonResponse
    {
        $codeModel = RedpackCode::where('code', $code)
            ->where('status', RedpackCodeStatus::Active)
            ->first();

        if (!$codeModel) {
            return ApiResponse::notFound('红包码无效或已被领取');
        }

        // 检查红包活动是否有效
        $redpack = $codeModel->redpack;

        if (!$redpack->isEnabled()) {
            return ApiResponse::error('红包活动已禁用', 1, null, 422);
        }

        if ($redpack->start_at && now()->isBefore($redpack->start_at)) {
            return ApiResponse::error('红包活动尚未开始', 1, null, 422);
        }

        if ($redpack->end_at && now()->isAfter($redpack->end_at)) {
            return ApiResponse::error('红包活动已结束', 1, null, 422);
        }

        // 更新红包码状态
        $codeModel->update([
            'user_id' => $request->user()->getKey(),
            'status' => RedpackCodeStatus::Claimed,
            'claimed_at' => now(),
            'claimed_ip' => $request->ip(),
        ]);

        return ApiResponse::success([
            'amount' => $codeModel->amount,
            'claimed_at' => $codeModel->claimed_at->toDateTimeString(),
        ], '红包领取成功');
    }
}
