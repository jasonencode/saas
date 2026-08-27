<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\User\UserDescendantResource;
use App\Http\Responses\ApiResponse;
use App\Models\User\UserRelation;
use App\Services\User\UserRelationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class UserRelationController extends Controller
{
    public function __construct(
        protected UserRelationService $userRelationService,
    ) {}

    /**
     * 获取用户下级列表
     */
    public function index(): JsonResponse
    {
        $userId = Auth::id();
        $relation = UserRelation::where('user_id', $userId)->first();

        if (!$relation) {
            return ApiResponse::success([
                'parent' => null,
                'list' => [],
            ]);
        }

        $descendants = $relation->getDescendants();
        $parent = $relation->parent;

        return ApiResponse::success([
            'parent' => $parent ? [
                'user_id' => $parent->id,
                'username' => $parent->username,
                'nickname' => $parent->profile?->nickname,
                'avatar' => $parent->profile?->avatar_url,
            ] : null,
            'list' => UserDescendantResource::collection($descendants),
        ]);
    }

    /**
     * 绑定上级（推荐人）
     */
    public function bind(int $parentId): JsonResponse
    {
        try {
            $user = Auth::user();
            $this->userRelationService->createRelation($user, $parentId);

            return ApiResponse::success(null, '绑定成功');
        } catch (\Throwable $exception) {
            return ApiResponse::error($exception->getMessage());
        }
    }

    /**
     * 数据概览
     *
     * 统计累积佣金、待结算、团队人数、推广订单。
     * 佣金、待结算及推广订单暂无数据来源，暂时返回虚拟数据。
     */
    public function overview(): JsonResponse
    {
        $userId = Auth::id();

        $teamCount = 0;
        $relation = UserRelation::where('user_id', $userId)->first();
        if ($relation) {
            $teamCount = $relation->getTeamStats()['team_count'];
        }

        return ApiResponse::success([
            'total_commission' => 0,
            'pending_settlement' => 0,
            'team_count' => $teamCount,
            'promotion_orders' => 0,
        ]);
    }
}
