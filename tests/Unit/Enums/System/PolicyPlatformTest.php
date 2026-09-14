<?php

namespace Tests\Unit\Enums\System;

use App\Enums\System\PolicyPlatform;
use PHPUnit\Framework\TestCase;

class PolicyPlatformTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame(1, PolicyPlatform::Backend->value);
        $this->assertSame(2, PolicyPlatform::Tenant->value);
        $this->assertSame(3, PolicyPlatform::Both->value);
    }

    public function test_enum_from_int(): void
    {
        $this->assertSame(PolicyPlatform::Backend, PolicyPlatform::from(1));
        $this->assertSame(PolicyPlatform::Tenant, PolicyPlatform::from(2));
        $this->assertSame(PolicyPlatform::Both, PolicyPlatform::from(3));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(PolicyPlatform::tryFrom(99));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(3, PolicyPlatform::cases());
    }

    public function test_backend_label(): void
    {
        $this->assertSame('总后台', PolicyPlatform::Backend->getLabel());
    }

    public function test_tenant_label(): void
    {
        $this->assertSame('租户后台', PolicyPlatform::Tenant->getLabel());
    }

    public function test_both_label(): void
    {
        $this->assertSame('全部平台', PolicyPlatform::Both->getLabel());
    }
}
