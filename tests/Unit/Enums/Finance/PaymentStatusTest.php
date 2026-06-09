<?php

namespace Tests\Unit\Enums\Finance;

use App\Enums\Finance\PaymentGateway;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Finance\VoucherStatus;
use PHPUnit\Framework\TestCase;

class PaymentStatusTest extends TestCase
{
    public function test_get_label(): void
    {
        $this->assertSame('待支付', PaymentStatus::Pending->getLabel());
        $this->assertSame('支付处理中', PaymentStatus::Processing->getLabel());
        $this->assertSame('已支付', PaymentStatus::Paid->getLabel());
        $this->assertSame('支付失败', PaymentStatus::Failed->getLabel());
        $this->assertSame('已取消', PaymentStatus::Canceled->getLabel());
        $this->assertSame('已退款', PaymentStatus::Refunded->getLabel());
    }

    public function test_get_color(): void
    {
        $this->assertSame('amber', PaymentStatus::Pending->getColor());
        $this->assertSame('emerald', PaymentStatus::Paid->getColor());
        $this->assertSame('red', PaymentStatus::Failed->getColor());
    }

    public function test_all_cases_have_labels_and_colors(): void
    {
        foreach (PaymentStatus::cases() as $case) {
            $this->assertNotEmpty($case->getLabel());
            $this->assertNotEmpty($case->getColor());
        }
    }
}

class PaymentGatewayTest extends TestCase
{
    public function test_get_label(): void
    {
        $this->assertSame('微信支付', PaymentGateway::Wechat->getLabel());
        $this->assertSame('支付宝', PaymentGateway::Alipay->getLabel());
        $this->assertSame('余额支付', PaymentGateway::Balance->getLabel());
        $this->assertSame('线下支付', PaymentGateway::Manual->getLabel());
    }

    public function test_get_color(): void
    {
        $this->assertSame('success', PaymentGateway::Wechat->getColor());
        $this->assertSame('info', PaymentGateway::Alipay->getColor());
        $this->assertSame('warning', PaymentGateway::Balance->getColor());
        $this->assertSame('indigo', PaymentGateway::Manual->getColor());
    }
}

class VoucherStatusTest extends TestCase
{
    public function test_get_label(): void
    {
        $this->assertSame('待执行', VoucherStatus::Pending->getLabel());
        $this->assertSame('执行中', VoucherStatus::Processing->getLabel());
        $this->assertSame('成功', VoucherStatus::Success->getLabel());
        $this->assertSame('失败', VoucherStatus::Failure->getLabel());
    }

    public function test_get_color(): void
    {
        $this->assertSame('gray', VoucherStatus::Pending->getColor());
        $this->assertSame('primary', VoucherStatus::Processing->getColor());
        $this->assertSame('success', VoucherStatus::Success->getColor());
        $this->assertSame('danger', VoucherStatus::Failure->getColor());
    }
}
