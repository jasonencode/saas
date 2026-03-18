<?php

namespace App\Extensions\TenantResolver;

use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;

class TenantResolver
{
    public static function resolve(): ?Tenant
    {
        static $cachedTenant = null;

        if ($cachedTenant !== null) {
            return $cachedTenant;
        }

        $tenantId = request()->header('X-Tenant-Id');

        if ($tenantId) {
            $cachedTenant = Cache::remember(
                key: "request_tenant:{$tenantId}",
                ttl: 3600,
                callback: static function () use ($tenantId) {
                    return Tenant::find($tenantId);
                }
            );

            return $cachedTenant;
        }

        return null;
    }
}
