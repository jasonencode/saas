<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\User\IdentityResource;
use App\Http\Responses\ApiResponse;
use App\Models\User\Identity;
use App\Services\User\IdentityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class IdentityController extends Controller
{
    public function __construct(
        private readonly IdentityService $identityService,
    ) {}

    /**
     * 当前用户有效身份列表
     *
     * @return JsonResponse 身份列表
     */
    public function index(): JsonResponse
    {
        $identities = $this->identityService->activeIdentities(Auth::user());

        return ApiResponse::success(IdentityResource::collection($identities));
    }

    /**
     * 可订阅的身份列表
     *
     * @param  int  $tenantId  租户ID
     *
     * @return JsonResponse 可订阅身份列表
     */
    public function available(int $tenantId): JsonResponse
    {
        $identities = Identity::where('tenant_id', $tenantId)
            ->where('can_subscribe', true)
            ->where('status', true)
            ->orderByDesc('sort')
            ->get();

        return ApiResponse::success(IdentityResource::collection($identities));
    }

    /**
     * 检查是否持有指定身份
     *
     * @param  Identity  $identity  身份
     *
     * @return JsonResponse 是否持有及即将过期状态
     */
    public function check(Identity $identity): JsonResponse
    {
        $user = Auth::user();

        $has = $this->identityService->has($user, $identity);
        $expiringSoon = false;

        if ($has) {
            $expiring = $this->identityService->expiringSoon($user, 7);
            $expiringSoon = $expiring->contains($identity);
        }

        return ApiResponse::success([
            'has' => $has,
            'expiring_soon' => $expiringSoon,
        ]);
    }
}
