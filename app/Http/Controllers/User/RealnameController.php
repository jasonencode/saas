<?php

namespace App\Http\Controllers\User;

use App\Enums\User\RealnameType;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRealnameRequest;
use App\Http\Resources\User\RealnameResource;
use App\Http\Resources\User\RealnameStatusResource;
use App\Http\Responses\ApiResponse;
use App\Models\User\UserRealname;
use App\Services\User\RealnameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Throwable;

class RealnameController extends Controller
{
    public function __construct(
        private readonly RealnameService $realnameService,
    ) {}

    /**
     * 当前用户最新一条实名认证记录
     *
     * 同一认证类型仅保留一条记录；按 id 倒序返回最新一条作为当前状态。
     * 未提交过实名认证时返回空响应。
     *
     * @return JsonResponse 认证记录
     */
    public function index(): JsonResponse
    {
        $realname = UserRealname::query()
            ->where('user_id', Auth::id())
            ->latest('id')
            ->first();

        if (!$realname) {
            return ApiResponse::success(null, '暂未提交实名认证');
        }

        return ApiResponse::success(RealnameResource::make($realname));
    }

    /**
     * 当前用户实名认证状态（轻量，无敏感资料）
     *
     * 以最新一条认证记录为准，返回是否已通过、当前状态等；未提交过认证时相关字段为 null。
     *
     * @return JsonResponse 认证状态
     */
    public function status(): JsonResponse
    {
        $realname = UserRealname::query()
            ->where('user_id', Auth::id())
            ->latest('id')
            ->first();

        return ApiResponse::success(RealnameStatusResource::make($realname));
    }

    /**
     * 提交（或重新提交）实名认证
     *
     * @param  StoreUserRealnameRequest  $request  认证请求
     *
     * @return JsonResponse 认证记录
     */
    public function store(StoreUserRealnameRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $type = RealnameType::from($validated['type']);

            $realname = $this->realnameService->submit(
                userId: Auth::id(),
                type: $type,
                data: Arr::except($validated, 'type'),
            );

            return ApiResponse::created(RealnameResource::make($realname->fresh()));
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage());
        }
    }
}
