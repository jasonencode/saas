<?php

namespace Tests\Unit\Enums\Campaign;

use App\Enums\Campaign\LotteryPrizeType;
use PHPUnit\Framework\TestCase;

class LotteryPrizeTypeTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('balance', LotteryPrizeType::Balance->value);
        $this->assertSame('points', LotteryPrizeType::Points->value);
        $this->assertSame('coupon', LotteryPrizeType::Coupon->value);
        $this->assertSame('redpack', LotteryPrizeType::Redpack->value);
        $this->assertSame('physical', LotteryPrizeType::Physical->value);
        $this->assertSame('none', LotteryPrizeType::None->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(LotteryPrizeType::Balance, LotteryPrizeType::from('balance'));
        $this->assertSame(LotteryPrizeType::Points, LotteryPrizeType::from('points'));
        $this->assertSame(LotteryPrizeType::Coupon, LotteryPrizeType::from('coupon'));
        $this->assertSame(LotteryPrizeType::Redpack, LotteryPrizeType::from('redpack'));
        $this->assertSame(LotteryPrizeType::Physical, LotteryPrizeType::from('physical'));
        $this->assertSame(LotteryPrizeType::None, LotteryPrizeType::from('none'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(LotteryPrizeType::tryFrom('invalid'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(6, LotteryPrizeType::cases());
    }

    public function test_balance_label_and_color(): void
    {
        $this->assertSame('余额', LotteryPrizeType::Balance->getLabel());
        $this->assertSame('success', LotteryPrizeType::Balance->getColor());
    }

    public function test_points_label_and_color(): void
    {
        $this->assertSame('积分', LotteryPrizeType::Points->getLabel());
        $this->assertSame('info', LotteryPrizeType::Points->getColor());
    }

    public function test_coupon_label_and_color(): void
    {
        $this->assertSame('优惠券', LotteryPrizeType::Coupon->getLabel());
        $this->assertSame('primary', LotteryPrizeType::Coupon->getColor());
    }

    public function test_redpack_label_and_color(): void
    {
        $this->assertSame('红包', LotteryPrizeType::Redpack->getLabel());
        $this->assertSame('danger', LotteryPrizeType::Redpack->getColor());
    }

    public function test_physical_label_and_color(): void
    {
        $this->assertSame('实物奖品', LotteryPrizeType::Physical->getLabel());
        $this->assertSame('warning', LotteryPrizeType::Physical->getColor());
    }

    public function test_none_label_and_color(): void
    {
        $this->assertSame('谢谢参与', LotteryPrizeType::None->getLabel());
        $this->assertSame('gray', LotteryPrizeType::None->getColor());
    }
}
