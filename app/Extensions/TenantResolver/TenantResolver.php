<?php

namespace App\Extensions\TenantResolver;

use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TenantResolver
{
    public static function current(): ?Tenant
    {
        return self::resolve();
    }

    public static function resolve(): ?Tenant
    {
        static $cachedTenant = null;

        if ($cachedTenant !== null) {
            return $cachedTenant;
        }

        $tenantId = request()->header('X-Tenant-Id');

        if (! $tenantId) {
            return null;
        }

        // 使用请求级缓存（静态变量），避免同一次请求中重复查询
        static $requestCache = [];

        if (isset($requestCache[$tenantId])) {
            return $requestCache[$tenantId];
        }

        // 从 Redis 缓存中获取租户数据（只缓存基本字段）
        $tenantData = Cache::remember(
            key: "tenant_data:$tenantId",
            ttl: 3600,
            callback: static function () use ($tenantId) {
                return Tenant::select(['id', 'name', 'status', 'expired_at'])
                    ->find($tenantId)
                    ?->toArray();
            }
        );

        if (! $tenantData) {
            throw new HttpException(400, '租户不存在');
        }

        // 检查租户状态
        if (isset($tenantData['status']) && ! $tenantData['status']) {
            throw new HttpException(403, '租户已被禁用');
        }

        // 检查租户是否过期
        if (isset($tenantData['expired_at']) && $tenantData['expired_at']) {
            $expiredAt = Carbon::parse($tenantData['expired_at']);
            if ($expiredAt->isPast()) {
                throw new HttpException(403, sprintf('租户已过期，过期时间：%s', $expiredAt->format('Y-m-d H:i:s')));
            }
        }

        // 手动创建 Tenant 实例，避免反序列化问题
        $cachedTenant = (new Tenant)->forceFill($tenantData);
        $cachedTenant->exists = true;
        $cachedTenant->wasRecentlyCreated = false;

        // 存入请求级缓存
        $requestCache[$tenantId] = $cachedTenant;

        return $cachedTenant;
    }
}
