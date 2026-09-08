<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
        $startedAt = microtime(true);

        // 链路追踪 ID：优先沿用网关/客户端传入，没有则生成。
        // 与日志上下文中的 request_id 保持一致，便于跨节点按 ID 检索日志
        $requestId = $request->header('X-Request-Id') ?: (string) Str::uuid();
        Log::withContext(['request_id' => $requestId]);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);
        $response->headers->set('X-Server-Id', config('custom.server_id'));

        // 内部性能信息仅在调试环境回给客户端
        if (config('app.debug')) {
            $response->headers->set('X-Duration-Ms', (int) round((microtime(true) - $startedAt) * 1000));
        }

        return $response;
    }
}
