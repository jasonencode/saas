<?php

namespace App\Http\Middleware;

use BackedEnum;
use Closure;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class EnsureSingleRequest
{
    /**
     * 处理请求，确保同一时间内同一用户/资源只有一个请求在处理
     *
     * 中间件参数格式：lock:{prefix},{timeout},{scope},{paramName}
     *
     * - prefix: 锁 key 前缀
     * - timeout: 超时时间（秒），默认 10
     * - scope: 锁粒度（user/resource/combined），默认 user
     *   - user: 按用户维度，key = {prefix}_{userId}
     *   - resource: 按资源维度，key = {prefix}_{routeParamId}
     *   - combined: 按用户+资源组合，key = {prefix}_{userId}_{routeParamId}
     * - paramName: 路由参数名（当 scope 为 resource 或 combined 时必填）
     */
    public function handle(Request $request, Closure $next, string $prefix, int $timeout = 10, string $scope = 'user', ?string $paramName = null): Response
    {
        $lockKey = $this->buildLockKey($request, $prefix, $scope, $paramName);

        $lock = Cache::lock($lockKey, $timeout);

        if (!$lock->get()) {
            abort(Response::HTTP_TOO_MANY_REQUESTS, '请勿重复提交');
        }

        try {
            return $next($request);
        } finally {
            $lock->release();
        }
    }

    /**
     * 构建锁 key
     */
    private function buildLockKey(Request $request, string $prefix, string $scope, ?string $paramName): string
    {
        return match ($scope) {
            'resource' => $prefix.'_'.$this->resolveRouteParam($request, $paramName),
            'combined' => $prefix.'_'.Auth::id().'_'.$this->resolveRouteParam($request, $paramName),
            default => $prefix.'_'.Auth::id(),
        };
    }

    /**
     * 从路由参数中解析资源 ID
     *
     * 路由模型绑定完成后，参数值是 Eloquent 模型而非标量；
     * 必须先取模型的 route key，避免把模型 JSON 当作锁 key。
     *
     * @param  Request  $request  当前请求
     * @param  string|null  $paramName  路由参数名
     *
     * @return string 资源标识
     */
    private function resolveRouteParam(Request $request, ?string $paramName): string
    {
        if ($paramName !== null) {
            $param = $request->route($paramName);

            if ($param !== null) {
                return $this->normalizeRouteParam($param);
            }
        }

        foreach ($request->route()?->parameters() ?? [] as $param) {
            if (is_numeric($param)) {
                return (string) $param;
            }

            if (is_object($param)) {
                return $this->normalizeRouteParam($param);
            }
        }

        return '0';
    }

    /**
     * 将路由参数标准化为字符串
     *
     * @param  mixed  $param  路由参数
     *
     * @return string 参数值
     */
    private function normalizeRouteParam(mixed $param): string
    {
        return match (true) {
            $param instanceof UrlRoutable => (string) $param->getRouteKey(),
            $param instanceof BackedEnum => (string) $param->value,
            is_object($param) && method_exists($param, 'getKey') => (string) $param->getKey(),
            default => (string) $param,
        };
    }
}
