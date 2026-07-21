<?php

namespace App\Support\TenantResolver;

use App\Models\System\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TenantResolver
{
    public static function current(): ?Tenant
    {
        return self::resolve();
    }

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

        $tenantData = Cache::remember(
            key: "tenant_data:$tenantId",
            ttl: 3600,
            callback: static function () use ($tenantId) {
                return Tenant::select(['id', 'name', 'status', 'expired_at'])
                    ->find($tenantId)
                    ?->toArray();
            }
        );

        if (!$tenantData) {
            throw new HttpException(400, '租户不存在');
        }

        if (isset($tenantData['status']) && !$tenantData['status']) {
            throw new HttpException(403, '租户已被禁用');
        }

        if (isset($tenantData['expired_at']) && $tenantData['expired_at']) {
            $expiredAt = Carbon::parse($tenantData['expired_at']);
            if ($expiredAt->isPast()) {
                throw new HttpException(403, sprintf('租户已过期，过期时间：%s', $expiredAt->format('Y-m-d H:i:s')));
            }
        }

        $tenant = new Tenant()->forceFill($tenantData);
        $tenant->exists = true;
        $tenant->wasRecentlyCreated = false;

        Context::add('tenant', $tenant);

        return $tenant;
    }
}
