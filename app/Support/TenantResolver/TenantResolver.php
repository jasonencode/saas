<?php

namespace App\Support\TenantResolver;

use App\Models\System\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * 租户解析器
 *
 * 从请求头 X-Tenant-Id 解析当前租户，并缓存结果。
 */
class TenantResolver
{
    /**
     * 获取当前租户
     *
     * @return Tenant|null 租户实例
     */
    public static function current(): ?Tenant
    {
        return self::resolve();
    }

    /**
     * 解析租户（从请求头 X-Tenant-Id 获取）
     *
     * @return Tenant|null 租户实例
     * @throws HttpException 租户不存在、已禁用或已过期
     *
     */
    public static function resolve(): ?Tenant
    {
        if (Context::has('tenant')) {
            return Context::get('tenant');
        }

        $tenantId = request()->header('X-Tenant-Id');

        if (!$tenantId) {
            Context::add('tenant');

            return null;
        }

        // 缓存 Tenant 模型实例本身，反序列化后即为完整模型，无需 forceFill 重建。
        // v2 前缀用于让旧的 array 缓存自然失效。
        /** @var Tenant|null $tenant */
        $tenant = Cache::remember(
            key: "tenant_data:v2:$tenantId",
            ttl: 3600,
            callback: static function () use ($tenantId) {
                return Tenant::select(['id', 'name', 'status', 'expired_at'])
                    ->find($tenantId);
            }
        );

        if (!$tenant) {
            throw new HttpException(400, '租户不存在');
        }

        if (!$tenant->status) {
            throw new HttpException(403, '租户已被禁用');
        }

        if ($tenant->isExpired()) {
            throw new HttpException(403, sprintf('租户已过期，过期时间：%s', $tenant->expired_at->format('Y-m-d H:i:s')));
        }

        Context::add('tenant', $tenant);

        return $tenant;
    }
}
