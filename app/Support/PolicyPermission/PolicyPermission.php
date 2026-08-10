<?php

namespace App\Support\PolicyPermission;

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
     * 请求级缓存
     *
     * @var array<int, Collection>
     */
    protected static array $cache = [];

    /**
     * 获取权限树
     *
     * @param  PolicyPlatform  $platform  平台类型
     *
     * @return Collection 权限树集合
     */
    public static function tree(PolicyPlatform $platform): Collection
    {
        $key = $platform->value;

        return static::$cache[$key] ?? (static::$cache[$key] = static::buildTree($platform));
    }

    /**
     * 清除缓存
     */
    public static function flushCache(): void
    {
        static::$cache = [];
    }

    /**
     * 构建权限树
     */
    protected static function buildTree(PolicyPlatform $platform): Collection
    {
        $list = [];

        foreach (Gate::policies() as $policyClass) {
            if (!class_exists($policyClass)) {
                continue;
            }

            $reflection = new ReflectionClass($policyClass);

            if (!$reflection->isSubclassOf(Policy::class)) {
                continue;
            }

            $instance = $reflection->newInstanceWithoutConstructor();
            $policyPlatform = $instance->getPlatform();

            if (!($platform->value & $policyPlatform)) {
                continue;
            }

            $list[] = [
                'method' => $policyClass,
                'name' => $instance->getModelName(),
                'group' => $instance->getGroupName(),
                'platform' => $policyPlatform,
                'children' => static::buildChildren($reflection, $policyClass, $platform),
            ];
        }

        return collect($list);
    }

    /**
     * 构建子权限列表
     *
     * @return array<int, array<string, mixed>>
     */
    protected static function buildChildren(ReflectionClass $reflection, string $policyClass, PolicyPlatform $platform): array
    {
        $children = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $attributes = $method->getAttributes(PolicyName::class);

            if ($attributes === []) {
                continue;
            }

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

        return $children;
    }
}
