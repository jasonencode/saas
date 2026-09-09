<?php

namespace App\Http\Controllers\User;

use App\Enums\User\RealnameType;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRealnameRequest;
use App\Http\Resources\User\RealnameResource;
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
     * 当前用户的实名认证记录
     *
     * 按认证类型区分，同一用户最多存在个人/企业各一条。
     *
     * @return JsonResponse 认证记录列表
     */
    public function index(): JsonResponse
    {
        $realnames = UserRealname::query()
            ->where('user_id', Auth::id())
            ->latest('id')
            ->get();

        return ApiResponse::success(RealnameResource::collection($realnames));
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
