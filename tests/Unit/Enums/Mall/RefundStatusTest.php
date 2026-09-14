<?php

namespace Tests\Unit\Enums\Mall;

use App\Enums\Mall\RefundStatus;
use PHPUnit\Framework\TestCase;

class RefundStatusTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('pending', RefundStatus::Pending->value);
        $this->assertSame('waiting_return', RefundStatus::WaitingReturn->value);
        $this->assertSame('shipping', RefundStatus::Shipping->value);
        $this->assertSame('received', RefundStatus::Received->value);
        $this->assertSame('processing', RefundStatus::Processing->value);
        $this->assertSame('completed', RefundStatus::Completed->value);
        $this->assertSame('rejected', RefundStatus::Rejected->value);
        $this->assertSame('cancelled', RefundStatus::Cancelled->value);
        $this->assertSame('failed', RefundStatus::Failed->value);
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(9, RefundStatus::cases());
    }

    public function test_get_label(): void
    {
        $this->assertSame('待审核', RefundStatus::Pending->getLabel());
        $this->assertSame('等待退货', RefundStatus::WaitingReturn->getLabel());
        $this->assertSame('退货中', RefundStatus::Shipping->getLabel());
        $this->assertSame('已签收', RefundStatus::Received->getLabel());
        $this->assertSame('退款处理中', RefundStatus::Processing->getLabel());
        $this->assertSame('退款完成', RefundStatus::Completed->getLabel());
        $this->assertSame('审核拒绝', RefundStatus::Rejected->getLabel());
        $this->assertSame('已取消', RefundStatus::Cancelled->getLabel());
        $this->assertSame('退款失败', RefundStatus::Failed->getLabel());
    }

    public function test_get_color(): void
    {
        $this->assertSame('warning', RefundStatus::Pending->getColor());
        $this->assertSame('orange', RefundStatus::WaitingReturn->getColor());
        $this->assertSame('info', RefundStatus::Shipping->getColor());
        $this->assertSame('purple', RefundStatus::Received->getColor());
        $this->assertSame('primary', RefundStatus::Processing->getColor());
        $this->assertSame('success', RefundStatus::Completed->getColor());
        $this->assertSame('danger', RefundStatus::Rejected->getColor());
        $this->assertSame('gray', RefundStatus::Cancelled->getColor());
        $this->assertSame('danger', RefundStatus::Failed->getColor());
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(RefundStatus::Pending, RefundStatus::from('pending'));
        $this->assertSame(RefundStatus::WaitingReturn, RefundStatus::from('waiting_return'));
        $this->assertSame(RefundStatus::Shipping, RefundStatus::from('shipping'));
        $this->assertSame(RefundStatus::Received, RefundStatus::from('received'));
        $this->assertSame(RefundStatus::Processing, RefundStatus::from('processing'));
        $this->assertSame(RefundStatus::Completed, RefundStatus::from('completed'));
        $this->assertSame(RefundStatus::Rejected, RefundStatus::from('rejected'));
        $this->assertSame(RefundStatus::Cancelled, RefundStatus::from('cancelled'));
        $this->assertSame(RefundStatus::Failed, RefundStatus::from('failed'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(RefundStatus::tryFrom('invalid'));
    }

    public function test_active_cases(): void
    {
        $activeCases = RefundStatus::activeCases();
        $this->assertCount(5, $activeCases);
        $this->assertContains(RefundStatus::Pending, $activeCases);
        $this->assertContains(RefundStatus::WaitingReturn, $activeCases);
        $this->assertContains(RefundStatus::Shipping, $activeCases);
        $this->assertContains(RefundStatus::Received, $activeCases);
        $this->assertContains(RefundStatus::Processing, $activeCases);
    }

    public function test_effective_cases(): void
    {
        $effectiveCases = RefundStatus::effectiveCases();
        $this->assertCount(6, $effectiveCases);
        $this->assertContains(RefundStatus::Completed, $effectiveCases);
    }

    public function test_terminal_cases(): void
    {
        $terminalCases = RefundStatus::terminalCases();
        $this->assertCount(4, $terminalCases);
        $this->assertContains(RefundStatus::Completed, $terminalCases);
        $this->assertContains(RefundStatus::Rejected, $terminalCases);
        $this->assertContains(RefundStatus::Cancelled, $terminalCases);
        $this->assertContains(RefundStatus::Failed, $terminalCases);
    }
}
