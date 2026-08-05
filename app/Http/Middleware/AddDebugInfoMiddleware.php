<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddDebugInfoMiddleware
{
    /**
     * 处理请求，添加调试信息头
     *
     * @param  Request  $request  当前请求
     * @param  Closure  $next  下一步处理
     *
     * @return Response 响应对象
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('X-Server-Id', config('custom.server_id'));

        return $response;
    }
}
