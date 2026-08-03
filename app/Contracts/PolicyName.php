<?php

namespace App\Contracts;

use App\Enums\System\PolicyPlatform;
use App\Enums\System\PolicyType;
use Attribute;

/**
 * 权限名称特性
 */
#[Attribute(Attribute::TARGET_METHOD)]
final readonly class PolicyName
{
    /**
     * 初始化权限名称特性
     *
     * @param  string  $policyName  权限名称
     * @param  string|null  $description  权限描述
     * @param  PolicyPlatform  $platform  权限平台
     * @param  PolicyType  $type  权限类型
     */
    public function __construct(
        private string $policyName,
        private ?string $description = null,
        private PolicyPlatform $platform = PolicyPlatform::Both,
        private PolicyType $type = PolicyType::Button,
    ) {}

    /**
     * 获取权限名称
     *
     * @return string 权限名称
     */
    public function getPolicyName(): string
    {
        return $this->policyName;
    }

    /**
     * 获取权限描述
     *
     * @return string|null 权限描述
     */
    public function getDescription(): ?string
    {
        return $this->description === '' ? null : $this->description;
    }

    /**
     * 获取平台值
     *
     * @return int 平台值
     */
    public function getPlatform(): int
    {
        return $this->platform->value;
    }

    /**
     * 获取权限类型
     *
     * @return PolicyType 权限类型
     */
    public function getType(): PolicyType
    {
        return $this->type;
    }
}
