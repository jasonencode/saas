<?php

namespace App\Http\Middleware;

use App\Services\System\BlackListService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BlackIpList
{
    /**
     * 处理请求，检查IP是否在黑名单中
     *
     * 判定必须在业务处理之前完成，禁止先执行控制器再拒绝，避免黑名单请求仍然产生副作用。
     *
     * @param  Request  $request  当前请求
     * @param  Closure  $next  下一步处理
     *
     * @throws HttpException IP 在黑名单中时
     *
     * @return Response 响应对象
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (service(BlackListService::class)->inBlackList((string) $request->ip())) {
            throw new HttpException(403, 'Not allowed');
        }

        return $next($request);
    }
}
