<?php

namespace App\Services;

use App\Enums\AdminType;
use App\Enums\PolicyPlatform;
use App\Factories\PolicyPermission;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Throwable;

class TenantService
{
    /**
     * 自动创建组织的用户结构
     *
     * @param  Tenant  $tenant
     * @return void
     * @throws Throwable
     */
    public function autoMakePermissions(Tenant $tenant): void
    {
        DB::transaction(function() use ($tenant) {
            $role = $tenant->roles()->create([
                'name' => 'Admin-'.$tenant->name,
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

            $staffer = $tenant->administrators()->create([
                'type' => AdminType::Tenant,
                'name' => $tenant->name.'管理员',
                'username' => $tenant->slug.'_admin',
                'password' => config('custom.tenant_user_default_password')
            ]);

            $staffer->roles()->attach($role);
        });
    }
}
