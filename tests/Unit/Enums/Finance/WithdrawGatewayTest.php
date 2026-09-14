<?php

namespace Tests\Unit\Enums\Finance;

use App\Enums\Finance\WithdrawGateway;
use PHPUnit\Framework\TestCase;

class WithdrawGatewayTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('wechat', WithdrawGateway::Wechat->value);
        $this->assertSame('alipay', WithdrawGateway::Alipay->value);
        $this->assertSame('bank', WithdrawGateway::Bank->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(WithdrawGateway::Wechat, WithdrawGateway::from('wechat'));
        $this->assertSame(WithdrawGateway::Alipay, WithdrawGateway::from('alipay'));
        $this->assertSame(WithdrawGateway::Bank, WithdrawGateway::from('bank'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(WithdrawGateway::tryFrom('invalid'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(3, WithdrawGateway::cases());
    }

    public function test_wechat_label_and_color(): void
    {
        $this->assertSame('微信提现', WithdrawGateway::Wechat->getLabel());
        $this->assertSame('success', WithdrawGateway::Wechat->getColor());
    }

    public function test_alipay_label_and_color(): void
    {
        $this->assertSame('支付宝提现', WithdrawGateway::Alipay->getLabel());
        $this->assertSame('info', WithdrawGateway::Alipay->getColor());
    }

    public function test_bank_label_and_color(): void
    {
        $this->assertSame('银行卡提现', WithdrawGateway::Bank->getLabel());
        $this->assertSame('warning', WithdrawGateway::Bank->getColor());
    }
}
