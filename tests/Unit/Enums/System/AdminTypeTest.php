<?php

namespace Tests\Unit\Enums\System;

use App\Enums\System\AdminType;
use PHPUnit\Framework\TestCase;

class AdminTypeTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('admin', AdminType::Admin->value);
        $this->assertSame('tenant', AdminType::Tenant->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(AdminType::Admin, AdminType::from('admin'));
        $this->assertSame(AdminType::Tenant, AdminType::from('tenant'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(AdminType::tryFrom('invalid'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(2, AdminType::cases());
    }

    public function test_admin_label_and_color(): void
    {
        $this->assertSame('管理员', AdminType::Admin->getLabel());
        $this->assertSame('success', AdminType::Admin->getColor());
    }

    public function test_tenant_label_and_color(): void
    {
        $this->assertSame('租户', AdminType::Tenant->getLabel());
        $this->assertSame('warning', AdminType::Tenant->getColor());
    }
}
