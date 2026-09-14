<?php

namespace Tests\Unit\Enums\Campaign;

use App\Enums\Campaign\LotteryDrawMode;
use PHPUnit\Framework\TestCase;

class LotteryDrawModeTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('free', LotteryDrawMode::Free->value);
        $this->assertSame('points', LotteryDrawMode::Points->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(LotteryDrawMode::Free, LotteryDrawMode::from('free'));
        $this->assertSame(LotteryDrawMode::Points, LotteryDrawMode::from('points'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(LotteryDrawMode::tryFrom('invalid'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(2, LotteryDrawMode::cases());
    }

    public function test_free_label_color_and_description(): void
    {
        $this->assertSame('免费抽奖', LotteryDrawMode::Free->getLabel());
        $this->assertSame('success', LotteryDrawMode::Free->getColor());
        $this->assertSame('每日免费N次', LotteryDrawMode::Free->getDescription());
    }

    public function test_points_label_color_and_description(): void
    {
        $this->assertSame('积分抽奖', LotteryDrawMode::Points->getLabel());
        $this->assertSame('warning', LotteryDrawMode::Points->getColor());
        $this->assertSame('每次消耗X积分', LotteryDrawMode::Points->getDescription());
    }
}
