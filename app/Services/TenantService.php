<?php

namespace App\Services;

use App\Enums\PolicyPlatform;
use App\Factories\PolicyPermission;
use App\Models\Tenant;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class TenantService
{
    /**
     * 自动创建组织的用户结构
     *
     * @throws Throwable
     */
    public function autoMakePermissions(Tenant $tenant): void
    {
        DB::transaction(static function () use ($tenant) {
            $role = $tenant->roles()->create([
                'name' => ucfirst($tenant->slug).'.Admin',
                'description' => '系统创建，超级管理，不可删除',
                'is_sys' => true,
            ]);

            $list = PolicyPermission::tree(PolicyPlatform::Tenant);

            foreach ($list as $item) {
                foreach ($item['children'] as $per) {
                    $role->permissions()->create([
                        'role_id' => $role->id,
                        'policy' => $item['method'],
                        'method' => $per['method'],
                    ]);
                }
            }
        });
    }

    /**
     * 重置租户到期时间
     */
    public function renew(Tenant $tenant, DateTimeInterface|string $expiredAt): void
    {
        $tenant->expired_at = $expiredAt instanceof DateTimeInterface
            ? Carbon::instance($expiredAt)
            : Carbon::parse($expiredAt);
        $tenant->save();
    }
}
