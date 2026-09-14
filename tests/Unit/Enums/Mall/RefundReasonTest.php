<?php

namespace Tests\Unit\Enums\Mall;

use App\Enums\Mall\RefundReason;
use PHPUnit\Framework\TestCase;

class RefundReasonTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('not_want', RefundReason::NotWant->value);
        $this->assertSame('wrong_order', RefundReason::WrongOrder->value);
        $this->assertSame('not_received', RefundReason::NotReceived->value);
        $this->assertSame('late_delivery', RefundReason::LateDelivery->value);
        $this->assertSame('quality', RefundReason::QualityIssue->value);
        $this->assertSame('damaged', RefundReason::Damaged->value);
        $this->assertSame('not_as_described', RefundReason::NotAsDescribed->value);
        $this->assertSame('size', RefundReason::SizeIssue->value);
        $this->assertSame('wrong_item', RefundReason::WrongItem->value);
        $this->assertSame('missing_item', RefundReason::MissingItem->value);
        $this->assertSame('counterfeit', RefundReason::Counterfeit->value);
        $this->assertSame('other', RefundReason::Other->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(RefundReason::NotWant, RefundReason::from('not_want'));
        $this->assertSame(RefundReason::WrongOrder, RefundReason::from('wrong_order'));
        $this->assertSame(RefundReason::NotReceived, RefundReason::from('not_received'));
        $this->assertSame(RefundReason::LateDelivery, RefundReason::from('late_delivery'));
        $this->assertSame(RefundReason::QualityIssue, RefundReason::from('quality'));
        $this->assertSame(RefundReason::Damaged, RefundReason::from('damaged'));
        $this->assertSame(RefundReason::NotAsDescribed, RefundReason::from('not_as_described'));
        $this->assertSame(RefundReason::SizeIssue, RefundReason::from('size'));
        $this->assertSame(RefundReason::WrongItem, RefundReason::from('wrong_item'));
        $this->assertSame(RefundReason::MissingItem, RefundReason::from('missing_item'));
        $this->assertSame(RefundReason::Counterfeit, RefundReason::from('counterfeit'));
        $this->assertSame(RefundReason::Other, RefundReason::from('other'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(RefundReason::tryFrom('invalid'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(12, RefundReason::cases());
    }

    public function test_not_want_label_and_color(): void
    {
        $this->assertSame('不想要了', RefundReason::NotWant->getLabel());
        $this->assertSame('gray', RefundReason::NotWant->getColor());
    }

    public function test_wrong_order_label_and_color(): void
    {
        $this->assertSame('拍错/多拍', RefundReason::WrongOrder->getLabel());
        $this->assertSame('info', RefundReason::WrongOrder->getColor());
    }

    public function test_not_received_label_and_color(): void
    {
        $this->assertSame('未收到货', RefundReason::NotReceived->getLabel());
        $this->assertSame('danger', RefundReason::NotReceived->getColor());
    }

    public function test_late_delivery_label_and_color(): void
    {
        $this->assertSame('未按时发货', RefundReason::LateDelivery->getLabel());
        $this->assertSame('warning', RefundReason::LateDelivery->getColor());
    }

    public function test_quality_issue_label_and_color(): void
    {
        $this->assertSame('质量问题', RefundReason::QualityIssue->getLabel());
        $this->assertSame('danger', RefundReason::QualityIssue->getColor());
    }

    public function test_damaged_label_and_color(): void
    {
        $this->assertSame('商品破损', RefundReason::Damaged->getLabel());
        $this->assertSame('danger', RefundReason::Damaged->getColor());
    }

    public function test_not_as_described_label_and_color(): void
    {
        $this->assertSame('描述不符', RefundReason::NotAsDescribed->getLabel());
        $this->assertSame('orange', RefundReason::NotAsDescribed->getColor());
    }

    public function test_size_issue_label_and_color(): void
    {
        $this->assertSame('尺寸不合适', RefundReason::SizeIssue->getLabel());
        $this->assertSame('info', RefundReason::SizeIssue->getColor());
    }

    public function test_wrong_item_label_and_color(): void
    {
        $this->assertSame('发错货', RefundReason::WrongItem->getLabel());
        $this->assertSame('purple', RefundReason::WrongItem->getColor());
    }

    public function test_missing_item_label_and_color(): void
    {
        $this->assertSame('少发/漏发', RefundReason::MissingItem->getLabel());
        $this->assertSame('pink', RefundReason::MissingItem->getColor());
    }

    public function test_counterfeit_label_and_color(): void
    {
        $this->assertSame('假货', RefundReason::Counterfeit->getLabel());
        $this->assertSame('danger', RefundReason::Counterfeit->getColor());
    }

    public function test_other_label_and_color(): void
    {
        $this->assertSame('其他', RefundReason::Other->getLabel());
        $this->assertSame('gray', RefundReason::Other->getColor());
    }
}
