<?php

namespace Tests\Unit\Enums\Campaign;

use App\Enums\Campaign\LotteryPrizeStatus;
use PHPUnit\Framework\TestCase;

class LotteryPrizeStatusTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('pending', LotteryPrizeStatus::Pending->value);
        $this->assertSame('fulfilled', LotteryPrizeStatus::Fulfilled->value);
        $this->assertSame('expired', LotteryPrizeStatus::Expired->value);
        $this->assertSame('cancelled', LotteryPrizeStatus::Cancelled->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(LotteryPrizeStatus::Pending, LotteryPrizeStatus::from('pending'));
        $this->assertSame(LotteryPrizeStatus::Fulfilled, LotteryPrizeStatus::from('fulfilled'));
        $this->assertSame(LotteryPrizeStatus::Expired, LotteryPrizeStatus::from('expired'));
        $this->assertSame(LotteryPrizeStatus::Cancelled, LotteryPrizeStatus::from('cancelled'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(LotteryPrizeStatus::tryFrom('invalid'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(4, LotteryPrizeStatus::cases());
    }

    public function test_pending_label_and_color(): void
    {
        $this->assertSame('待兑奖', LotteryPrizeStatus::Pending->getLabel());
        $this->assertSame('warning', LotteryPrizeStatus::Pending->getColor());
    }

    public function test_fulfilled_label_and_color(): void
    {
        $this->assertSame('已兑奖', LotteryPrizeStatus::Fulfilled->getLabel());
        $this->assertSame('success', LotteryPrizeStatus::Fulfilled->getColor());
    }

    public function test_expired_label_and_color(): void
    {
        $this->assertSame('已过期', LotteryPrizeStatus::Expired->getLabel());
        $this->assertSame('gray', LotteryPrizeStatus::Expired->getColor());
    }

    public function test_cancelled_label_and_color(): void
    {
        $this->assertSame('已取消', LotteryPrizeStatus::Cancelled->getLabel());
        $this->assertSame('danger', LotteryPrizeStatus::Cancelled->getColor());
    }
}
