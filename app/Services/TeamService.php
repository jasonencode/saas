<?php

namespace App\Services;

use App\Factories\PolicyPermission;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Throwable;

class TeamService
{
    /**
     * 自动创建组织的用户结构
     *
     * @param  Team  $team
     * @return void
     * @throws Throwable
     */
    public function autoMakePermissions(Team $team): void
    {
        DB::transaction(function() use ($team) {
            $role = $team->roles()->create([
                'name' => 'Admin-'.$team->name,
                'description' => '系统创建，超级管理，不可删除',
                'is_sys' => true,
            ]);

            $list = PolicyPermission::tree()->get('Saas');

            foreach ($list as $item) {
                foreach ($item['children'] as $per) {
                    $role->permissions()->create([
                        'role_id' => $role->id,
                        'policy' => $item['method'],
                        'method' => $per['method'],
                    ]);
                }
            }

            $staffer = $team->staffers()->create([
                'name' => '管理员',
                'username' => $team->slug.'_admin',
                'password' => '@Aa123456',
                'status' => true,
            ]);

            $staffer->roles()->attach($role);
        });
    }
}