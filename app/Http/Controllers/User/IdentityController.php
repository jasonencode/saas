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
     */
    public function index(): JsonResponse
    {
        $identities = $this->identityService->activeIdentities(Auth::user());

        return ApiResponse::success(IdentityResource::collection($identities));
    }

    /**
     * 可订阅的身份列表
     */
    public function available(): JsonResponse
    {
        $user = Auth::user();

        $identities = Identity::where('tenant_id', $user->tenant_id)
            ->where('can_subscribe', true)
            ->where('status', true)
            ->orderBy('sort')
            ->get();

        return ApiResponse::success(IdentityResource::collection($identities));
    }

    /**
     * 检查是否持有指定身份
     */
    public function check(Identity $identity): JsonResponse
    {
        $user = Auth::user();

        if ($identity->tenant_id !== $user->tenant_id) {
            return ApiResponse::notFound('身份不存在');
        }

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
