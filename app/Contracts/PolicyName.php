<?php

namespace App\Contracts;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class PolicyName
{
    public function __construct(
        private string $policyName,
        private ?string $description = null
    ) {
    }

    public function getPolicyName(): string
    {
        return $this->policyName;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
