<?php

namespace App\Factories;

use App\Contracts\PolicyName;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use ReflectionClass;

class PolicyPermission
{
    public static function tree(): Collection
    {
        $list = [];

        foreach (Gate::policies() as $policy) {
            $class = new ReflectionClass($policy);
            $instance = $class->newInstance();

            $children = [];
            foreach ($class->getMethods() as $method) {
                foreach ($method->getAttributes(PolicyName::class) as $attribute) {
                    $children[] = [
                        'method' => $method->getName(),
                        'name' => $attribute->newInstance()->getPolicyName(),
                        'description' => $attribute->newInstance()->getDescription(),
                    ];
                }
            }

            $list[] = [
                'method' => $class->getName(),
                'name' => $instance->getModelName(),
                'group' => $instance->getGroupName(),
                'children' => $children,
            ];
        }

        return collect($list)->groupBy('group');
    }
}