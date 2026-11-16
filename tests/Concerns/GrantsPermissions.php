<?php

namespace Tests\Concerns;

use App\Contracts\Attributes\PolicyName;
use App\Enums\System\PolicyPlatform;
use App\Models\System\Administrator;
use App\Models\System\AdminRole;
use App\Models\System\AdminRolePermission;
use ReflectionClass;
use ReflectionMethod;

/**
 * 面板测试授权
 *
 * 通过给管理员挂载最小权限角色来满足策略检查，代替
 * `Gate::before(fn () => true)` 这种整体绕过策略的写法。
 */
trait GrantsPermissions
{
    /**
     * 给管理员授予指定策略的全部操作权限
     *
     * @param  Administrator  $administrator  管理员
     * @param  array<int, class-string>  $policies  策略类名
     */
    protected function grantPolicyPermissions(Administrator $administrator, array $policies): void
    {
        $role = AdminRole::create([
            'name' => '测试角色-'.$administrator->getKey(),
            'is_sys' => false,
            // 租户面板 boot 后 AdminRole 会挂租户全局作用域，角色必须归属当前租户
            'tenant_id' => filament()->getTenant()?->getKey(),
        ]);

        foreach ($policies as $policy) {
            $this->grantPolicy($role, $policy);
        }

        $administrator->roles()->attach($role);
        $administrator->unsetRelation('roles');
    }

    /**
     * 写入单个策略的权限行
     *
     * @param  AdminRole  $role  角色
     * @param  class-string  $policy  策略类名
     */
    private function grantPolicy(AdminRole $role, string $policy): void
    {
        $reflection = new ReflectionClass($policy);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $attributes = $method->getAttributes(PolicyName::class);

            if ($attributes === []) {
                continue;
            }

            /** @var PolicyName $name */
            $name = $attributes[0]->newInstance();

            if ($name->getPlatform() !== PolicyPlatform::Both->value
                && $name->getPlatform() !== $this->policyPanelPlatform()->value) {
                continue;
            }

            AdminRolePermission::create([
                'role_id' => $role->getKey(),
                'policy' => $policy,
                'method' => $method->getName(),
            ]);
        }
    }

    /**
     * 当前面板对应的权限平台
     */
    private function policyPanelPlatform(): PolicyPlatform
    {
        return filament()->getCurrentPanel()?->getId() === 'backend'
            ? PolicyPlatform::Backend
            : PolicyPlatform::Tenant;
    }
}
