<?php

namespace Tests\Unit\Enums\Mall;

use App\Enums\Mall\RegionLevel;
use PHPUnit\Framework\TestCase;

class RegionLevelTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('p', RegionLevel::Province->value);
        $this->assertSame('c', RegionLevel::City->value);
        $this->assertSame('d', RegionLevel::District->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(RegionLevel::Province, RegionLevel::from('p'));
        $this->assertSame(RegionLevel::City, RegionLevel::from('c'));
        $this->assertSame(RegionLevel::District, RegionLevel::from('d'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(RegionLevel::tryFrom('invalid'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(3, RegionLevel::cases());
    }

    public function test_province_label_and_color(): void
    {
        $this->assertSame('省级', RegionLevel::Province->getLabel());
        $this->assertSame('primary', RegionLevel::Province->getColor());
    }

    public function test_city_label_and_color(): void
    {
        $this->assertSame('市级', RegionLevel::City->getLabel());
        $this->assertSame('success', RegionLevel::City->getColor());
    }

    public function test_district_label_and_color(): void
    {
        $this->assertSame('区级', RegionLevel::District->getLabel());
        $this->assertSame('warning', RegionLevel::District->getColor());
    }
}
