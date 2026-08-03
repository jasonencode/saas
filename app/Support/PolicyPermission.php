<?php

namespace App\Support;

use App\Contracts\Policy;
use App\Contracts\PolicyName;
use App\Enums\System\PolicyPlatform;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use ReflectionClass;
use ReflectionMethod;

/**
 * 权限策略管理
 *
 * 通过反射扫描已注册的 Gate 策略，构建权限树。
 */
class PolicyPermission
{
    /**
     * 获取权限树
     *
     * @param  PolicyPlatform  $platform  平台类型
     *
     * @return Collection 权限树集合
     */
    public static function tree(PolicyPlatform $platform): Collection
    {
        $list = [];

        foreach (Gate::policies() as $policyClass) {
            $reflection = new ReflectionClass($policyClass);
            $instance = $reflection->newInstanceWithoutConstructor();

            if (!$instance instanceof Policy) {
                continue;
            }

            // 检查平台权限
            $policyPlatform = $instance->getPlatform();

            if (!($platform->value & $policyPlatform)) {
                continue;
            }

            // 构建子权限
            $children = [];
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                $attributes = $method->getAttributes(PolicyName::class);

                if (!empty($attributes)) {
                    $attribute = $attributes[0]->newInstance();
                    $abilityPlatform = $attribute->getPlatform();

                    if ($platform->value & $abilityPlatform) {
                        $children[] = [
                            'method' => $policyClass.'@'.$method->getName(),
                            'name' => $attribute->getPolicyName(),
                            'platform' => $abilityPlatform,
                            'description' => $attribute->getDescription(),
                            'type' => $attribute->getType()->value,
                        ];
                    }
                }
            }

            $list[] = [
                'method' => $policyClass,
                'name' => $instance->getModelName(),
                'group' => $instance->getGroupName(),
                'platform' => $policyPlatform,
                'children' => $children,
            ];
        }

        return collect($list);
    }
}
