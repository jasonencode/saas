<?php

namespace Tests\Unit\Enums\Mall;

use App\Enums\Mall\RefundLogAction;
use PHPUnit\Framework\TestCase;

class RefundLogActionTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('created', RefundLogAction::Created->value);
        $this->assertSame('approved', RefundLogAction::Approved->value);
        $this->assertSame('rejected', RefundLogAction::Rejected->value);
        $this->assertSame('cancelled', RefundLogAction::Cancelled->value);
        $this->assertSame('waiting_return', RefundLogAction::WaitingReturn->value);
        $this->assertSame('return_shipped', RefundLogAction::ReturnShipped->value);
        $this->assertSame('return_received', RefundLogAction::ReturnReceived->value);
        $this->assertSame('processing', RefundLogAction::Processing->value);
        $this->assertSame('completed', RefundLogAction::Completed->value);
        $this->assertSame('failed', RefundLogAction::Failed->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(RefundLogAction::Created, RefundLogAction::from('created'));
        $this->assertSame(RefundLogAction::Approved, RefundLogAction::from('approved'));
        $this->assertSame(RefundLogAction::Rejected, RefundLogAction::from('rejected'));
        $this->assertSame(RefundLogAction::Cancelled, RefundLogAction::from('cancelled'));
        $this->assertSame(RefundLogAction::WaitingReturn, RefundLogAction::from('waiting_return'));
        $this->assertSame(RefundLogAction::ReturnShipped, RefundLogAction::from('return_shipped'));
        $this->assertSame(RefundLogAction::ReturnReceived, RefundLogAction::from('return_received'));
        $this->assertSame(RefundLogAction::Processing, RefundLogAction::from('processing'));
        $this->assertSame(RefundLogAction::Completed, RefundLogAction::from('completed'));
        $this->assertSame(RefundLogAction::Failed, RefundLogAction::from('failed'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(RefundLogAction::tryFrom('invalid'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(10, RefundLogAction::cases());
    }

    public function test_created_label_and_color(): void
    {
        $this->assertSame('创建退款', RefundLogAction::Created->getLabel());
        $this->assertSame('gray', RefundLogAction::Created->getColor());
    }

    public function test_approved_label_and_color(): void
    {
        $this->assertSame('审核通过', RefundLogAction::Approved->getLabel());
        $this->assertSame('green', RefundLogAction::Approved->getColor());
    }

    public function test_rejected_label_and_color(): void
    {
        $this->assertSame('审核拒绝', RefundLogAction::Rejected->getLabel());
        $this->assertSame('red', RefundLogAction::Rejected->getColor());
    }

    public function test_cancelled_label_and_color(): void
    {
        $this->assertSame('取消退款', RefundLogAction::Cancelled->getLabel());
        $this->assertSame('orange', RefundLogAction::Cancelled->getColor());
    }

    public function test_waiting_return_label_and_color(): void
    {
        $this->assertSame('等待退货', RefundLogAction::WaitingReturn->getLabel());
        $this->assertSame('violet', RefundLogAction::WaitingReturn->getColor());
    }

    public function test_return_shipped_label_and_color(): void
    {
        $this->assertSame('退货发货', RefundLogAction::ReturnShipped->getLabel());
        $this->assertSame('cyan', RefundLogAction::ReturnShipped->getColor());
    }

    public function test_return_received_label_and_color(): void
    {
        $this->assertSame('退货签收', RefundLogAction::ReturnReceived->getLabel());
        $this->assertSame('teal', RefundLogAction::ReturnReceived->getColor());
    }

    public function test_processing_label_and_color(): void
    {
        $this->assertSame('开始处理', RefundLogAction::Processing->getLabel());
        $this->assertSame('blue', RefundLogAction::Processing->getColor());
    }

    public function test_completed_label_and_color(): void
    {
        $this->assertSame('退款完成', RefundLogAction::Completed->getLabel());
        $this->assertSame('emerald', RefundLogAction::Completed->getColor());
    }

    public function test_failed_label_and_color(): void
    {
        $this->assertSame('退款失败', RefundLogAction::Failed->getLabel());
        $this->assertSame('red', RefundLogAction::Failed->getColor());
    }
}
