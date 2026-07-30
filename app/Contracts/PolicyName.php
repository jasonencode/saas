<?php

namespace App\Contracts;

use App\Enums\System\PolicyPlatform;
use App\Enums\System\PolicyType;
use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class PolicyName
{
    public function __construct(
        private string $policyName,
        private ?string $description = null,
        private PolicyPlatform $platform = PolicyPlatform::Both,
        private PolicyType $type = PolicyType::Button,
    ) {}

    public function getPolicyName(): string
    {
        return $this->policyName;
    }

    public function getDescription(): ?string
    {
        return $this->description === '' ? null : $this->description;
    }

    public function getPlatform(): int
    {
        return $this->platform->value;
    }

    public function getType(): PolicyType
    {
        return $this->type;
    }
}
