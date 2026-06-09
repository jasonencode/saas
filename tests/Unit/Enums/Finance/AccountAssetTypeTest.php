<?php

namespace Tests\Unit\Enums\Finance;

use App\Enums\Finance\AccountAssetType;
use PHPUnit\Framework\TestCase;

class AccountAssetTypeTest extends TestCase
{
    public function test_from_field_returns_balance(): void
    {
        $this->assertSame(AccountAssetType::Balance, AccountAssetType::fromField('balance'));
    }

    public function test_from_field_returns_points(): void
    {
        $this->assertSame(AccountAssetType::Points, AccountAssetType::fromField('points'));
    }

    public function test_from_field_returns_null_for_unknown(): void
    {
        $this->assertNull(AccountAssetType::fromField('unknown'));
    }

    public function test_get_field(): void
    {
        $this->assertSame('balance', AccountAssetType::Balance->getField());
        $this->assertSame('points', AccountAssetType::Points->getField());
    }

    public function test_get_label(): void
    {
        $this->assertSame('余额', AccountAssetType::Balance->getLabel());
        $this->assertSame('积分', AccountAssetType::Points->getLabel());
    }

    public function test_get_color(): void
    {
        $this->assertSame('primary', AccountAssetType::Balance->getColor());
        $this->assertSame('success', AccountAssetType::Points->getColor());
    }

    public function test_from_field_and_get_field_are_inverse(): void
    {
        foreach (AccountAssetType::cases() as $case) {
            $this->assertSame($case, AccountAssetType::fromField($case->getField()));
        }
    }
}
