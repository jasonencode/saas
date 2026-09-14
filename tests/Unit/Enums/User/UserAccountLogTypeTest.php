<?php

namespace Tests\Unit\Enums\User;

use App\Enums\User\UserAccountLogType;
use PHPUnit\Framework\TestCase;

class UserAccountLogTypeTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('system', UserAccountLogType::System->value);
        $this->assertSame('recharge', UserAccountLogType::Recharge->value);
        $this->assertSame('consume', UserAccountLogType::Consume->value);
        $this->assertSame('refund', UserAccountLogType::Refund->value);
        $this->assertSame('reward', UserAccountLogType::Reward->value);
        $this->assertSame('freeze', UserAccountLogType::Freeze->value);
        $this->assertSame('unfreeze', UserAccountLogType::Unfreeze->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(UserAccountLogType::System, UserAccountLogType::from('system'));
        $this->assertSame(UserAccountLogType::Recharge, UserAccountLogType::from('recharge'));
        $this->assertSame(UserAccountLogType::Consume, UserAccountLogType::from('consume'));
        $this->assertSame(UserAccountLogType::Refund, UserAccountLogType::from('refund'));
        $this->assertSame(UserAccountLogType::Reward, UserAccountLogType::from('reward'));
        $this->assertSame(UserAccountLogType::Freeze, UserAccountLogType::from('freeze'));
        $this->assertSame(UserAccountLogType::Unfreeze, UserAccountLogType::from('unfreeze'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(UserAccountLogType::tryFrom('invalid'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(7, UserAccountLogType::cases());
    }

    public function test_system_label_and_color(): void
    {
        $this->assertSame('系统调整', UserAccountLogType::System->getLabel());
        $this->assertSame('neutral', UserAccountLogType::System->getColor());
    }

    public function test_recharge_label_and_color(): void
    {
        $this->assertSame('充值', UserAccountLogType::Recharge->getLabel());
        $this->assertSame('emerald', UserAccountLogType::Recharge->getColor());
    }

    public function test_consume_label_and_color(): void
    {
        $this->assertSame('消费', UserAccountLogType::Consume->getLabel());
        $this->assertSame('rose', UserAccountLogType::Consume->getColor());
    }

    public function test_refund_label_and_color(): void
    {
        $this->assertSame('退款', UserAccountLogType::Refund->getLabel());
        $this->assertSame('teal', UserAccountLogType::Refund->getColor());
    }

    public function test_reward_label_and_color(): void
    {
        $this->assertSame('奖励', UserAccountLogType::Reward->getLabel());
        $this->assertSame('amber', UserAccountLogType::Reward->getColor());
    }

    public function test_freeze_label_and_color(): void
    {
        $this->assertSame('冻结', UserAccountLogType::Freeze->getLabel());
        $this->assertSame('red', UserAccountLogType::Freeze->getColor());
    }

    public function test_unfreeze_label_and_color(): void
    {
        $this->assertSame('解冻', UserAccountLogType::Unfreeze->getLabel());
        $this->assertSame('sky', UserAccountLogType::Unfreeze->getColor());
    }
}
