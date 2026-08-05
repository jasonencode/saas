<?php

namespace App\Http\Middleware;

use App\Services\System\BlackListService;
use Closure;
use Illuminate\Http\Request;
use Laravel\Horizon\Exceptions\ForbiddenException;
use Symfony\Component\HttpFoundation\Response;

class BlackIpList
{
    /**
     * 处理请求，检查IP是否在黑名单中
     *
     * @param  Request  $request  当前请求
     * @param  Closure  $next  下一步处理
     *
     * @throws ForbiddenException IP在黑名单中时
     *
     * @return Response 响应对象
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (service(BlackListService::class)->inBlackList($request->ip())) {
            throw new ForbiddenException(403, 'Not allowed');
        }

        return $response;
    }
}
