<?php

namespace Tests\Unit\Enums\User;

use App\Enums\User\IdentityChannel;
use PHPUnit\Framework\TestCase;

class IdentityChannelTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('Auto', IdentityChannel::Auto->value);
        $this->assertSame('Reg', IdentityChannel::Reg->value);
        $this->assertSame('Subscribe', IdentityChannel::Subscribe->value);
        $this->assertSame('System', IdentityChannel::System->value);
        $this->assertSame('Card', IdentityChannel::Card->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(IdentityChannel::Auto, IdentityChannel::from('Auto'));
        $this->assertSame(IdentityChannel::Reg, IdentityChannel::from('Reg'));
        $this->assertSame(IdentityChannel::Subscribe, IdentityChannel::from('Subscribe'));
        $this->assertSame(IdentityChannel::System, IdentityChannel::from('System'));
        $this->assertSame(IdentityChannel::Card, IdentityChannel::from('Card'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(IdentityChannel::tryFrom('invalid'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(5, IdentityChannel::cases());
    }

    public function test_auto_label_and_color(): void
    {
        $this->assertSame('自动变更', IdentityChannel::Auto->getLabel());
        $this->assertSame('sky', IdentityChannel::Auto->getColor());
    }

    public function test_reg_label_and_color(): void
    {
        $this->assertSame('注册默认', IdentityChannel::Reg->getLabel());
        $this->assertSame('emerald', IdentityChannel::Reg->getColor());
    }

    public function test_subscribe_label_and_color(): void
    {
        $this->assertSame('付费订阅', IdentityChannel::Subscribe->getLabel());
        $this->assertSame('purple', IdentityChannel::Subscribe->getColor());
    }

    public function test_system_label_and_color(): void
    {
        $this->assertSame('后台变更', IdentityChannel::System->getLabel());
        $this->assertSame('slate', IdentityChannel::System->getColor());
    }

    public function test_card_label_and_color(): void
    {
        $this->assertSame('会员卡激活', IdentityChannel::Card->getLabel());
        $this->assertSame('amber', IdentityChannel::Card->getColor());
    }
}
