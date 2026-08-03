<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use App\Models\Mall\StoreConfigure;
use App\Support\TenantResolver\TenantResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 商城开通校验中间件
 *
 * 校验当前请求所属租户的商城是否已开通（StoreConfigure.enabled），
 * 未开通时统一返回 403，避免商城模块对外暴露给未开通的租户。
 */
class EnsureStoreIsOpened
{
    /**
     * 处理 incoming 请求
     *
     * @param  Request  $request  当前请求
     * @param  Closure  $next  下一步处理
     *
     * @return Response 响应
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = TenantResolver::current();

        if (!$tenant) {
            return ApiResponse::forbidden('无法识别当前租户');
        }

        if (!StoreConfigure::isTenantOpened((int) $tenant->getKey())) {
            return ApiResponse::forbidden('商城尚未开通，请先申请开店');
        }

        return $next($request);
    }
}
