<?php

namespace Tests\Unit\Enums\Finance;

use App\Enums\Finance\RechargeOrderStatus;
use PHPUnit\Framework\TestCase;

class RechargeOrderStatusTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('pending', RechargeOrderStatus::Pending->value);
        $this->assertSame('processing', RechargeOrderStatus::Processing->value);
        $this->assertSame('paid', RechargeOrderStatus::Paid->value);
        $this->assertSame('completed', RechargeOrderStatus::Completed->value);
        $this->assertSame('failed', RechargeOrderStatus::Failed->value);
        $this->assertSame('canceled', RechargeOrderStatus::Canceled->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(RechargeOrderStatus::Pending, RechargeOrderStatus::from('pending'));
        $this->assertSame(RechargeOrderStatus::Processing, RechargeOrderStatus::from('processing'));
        $this->assertSame(RechargeOrderStatus::Paid, RechargeOrderStatus::from('paid'));
        $this->assertSame(RechargeOrderStatus::Completed, RechargeOrderStatus::from('completed'));
        $this->assertSame(RechargeOrderStatus::Failed, RechargeOrderStatus::from('failed'));
        $this->assertSame(RechargeOrderStatus::Canceled, RechargeOrderStatus::from('canceled'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(RechargeOrderStatus::tryFrom('invalid'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(6, RechargeOrderStatus::cases());
    }

    public function test_pending_label_and_color(): void
    {
        $this->assertSame('待支付', RechargeOrderStatus::Pending->getLabel());
        $this->assertSame('amber', RechargeOrderStatus::Pending->getColor());
    }

    public function test_processing_label_and_color(): void
    {
        $this->assertSame('支付处理中', RechargeOrderStatus::Processing->getLabel());
        $this->assertSame('sky', RechargeOrderStatus::Processing->getColor());
    }

    public function test_paid_label_and_color(): void
    {
        $this->assertSame('已支付', RechargeOrderStatus::Paid->getLabel());
        $this->assertSame('info', RechargeOrderStatus::Paid->getColor());
    }

    public function test_completed_label_and_color(): void
    {
        $this->assertSame('已完成', RechargeOrderStatus::Completed->getLabel());
        $this->assertSame('emerald', RechargeOrderStatus::Completed->getColor());
    }

    public function test_failed_label_and_color(): void
    {
        $this->assertSame('支付失败', RechargeOrderStatus::Failed->getLabel());
        $this->assertSame('red', RechargeOrderStatus::Failed->getColor());
    }

    public function test_canceled_label_and_color(): void
    {
        $this->assertSame('已取消', RechargeOrderStatus::Canceled->getLabel());
        $this->assertSame('rose', RechargeOrderStatus::Canceled->getColor());
    }
}
