<?php

namespace App\Factories;

use App\Contracts\PolicyName;
use App\Enums\PolicyPlatform;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use ReflectionClass;

class PolicyPermission
{
    public static function tree(PolicyPlatform $platform): Collection
    {
        $list = [];

        foreach (Gate::policies() as $policy) {
            $class = new ReflectionClass($policy);
            $instance = $class->newInstance();

            if ($platform->value & $instance->getPlatform()) {
                $children = [];
                foreach ($class->getMethods() as $method) {
                    foreach ($method->getAttributes(PolicyName::class) as $attribute) {
                        if ($platform->value & $attribute->newInstance()->getPlatform()) {
                            $children[] = [
                                'method' => $method->getName(),
                                'name' => $attribute->newInstance()->getPolicyName(),
                                'platform' => $attribute->newInstance()->getPlatform(),
                                'description' => $attribute->newInstance()->getDescription(),
                            ];
                        }
                    }
                }

                $list[] = [
                    'method' => $class->getName(),
                    'name' => $instance->getModelName(),
                    'group' => $instance->getGroupName(),
                    'platform' => $instance->getPlatform(),
                    'children' => $children,
                ];
            }
        }

        return collect($list);
    }
}
