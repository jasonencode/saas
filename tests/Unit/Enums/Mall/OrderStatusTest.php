<?php

namespace Tests\Unit\Enums\Mall;

use App\Enums\Mall\OrderStatus;
use PHPUnit\Framework\TestCase;

class OrderStatusTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('pending', OrderStatus::Pending->value);
        $this->assertSame('canceled', OrderStatus::Canceled->value);
        $this->assertSame('paid', OrderStatus::Paid->value);
        $this->assertSame('preparing', OrderStatus::Preparing->value);
        $this->assertSame('partially', OrderStatus::PartiallyShipped->value);
        $this->assertSame('delivered', OrderStatus::Delivered->value);
        $this->assertSame('signed', OrderStatus::Signed->value);
        $this->assertSame('completed', OrderStatus::Completed->value);
        $this->assertSame('pickup_pending', OrderStatus::PickupPending->value);
        $this->assertSame('verified', OrderStatus::Verified->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(OrderStatus::Pending, OrderStatus::from('pending'));
        $this->assertSame(OrderStatus::Paid, OrderStatus::from('paid'));
        $this->assertSame(OrderStatus::Completed, OrderStatus::from('completed'));
        $this->assertSame(OrderStatus::PickupPending, OrderStatus::from('pickup_pending'));
        $this->assertSame(OrderStatus::Verified, OrderStatus::from('verified'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(OrderStatus::tryFrom('invalid'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(10, OrderStatus::cases());
    }

    public function test_pending_label_and_color(): void
    {
        $this->assertSame('待付款', OrderStatus::Pending->getLabel());
        $this->assertSame('amber', OrderStatus::Pending->getColor());
    }

    public function test_canceled_label_and_color(): void
    {
        $this->assertSame('已取消', OrderStatus::Canceled->getLabel());
        $this->assertSame('red', OrderStatus::Canceled->getColor());
    }

    public function test_paid_label_and_color(): void
    {
        $this->assertSame('待发货', OrderStatus::Paid->getLabel());
        $this->assertSame('blue', OrderStatus::Paid->getColor());
    }

    public function test_preparing_label_and_color(): void
    {
        $this->assertSame('备货中', OrderStatus::Preparing->getLabel());
        $this->assertSame('sky', OrderStatus::Preparing->getColor());
    }

    public function test_partially_shipped_label_and_color(): void
    {
        $this->assertSame('部分发货', OrderStatus::PartiallyShipped->getLabel());
        $this->assertSame('cyan', OrderStatus::PartiallyShipped->getColor());
    }

    public function test_delivered_label_and_color(): void
    {
        $this->assertSame('已发货', OrderStatus::Delivered->getLabel());
        $this->assertSame('indigo', OrderStatus::Delivered->getColor());
    }

    public function test_signed_label_and_color(): void
    {
        $this->assertSame('已签收', OrderStatus::Signed->getLabel());
        $this->assertSame('teal', OrderStatus::Signed->getColor());
    }

    public function test_completed_label_and_color(): void
    {
        $this->assertSame('已完成', OrderStatus::Completed->getLabel());
        $this->assertSame('emerald', OrderStatus::Completed->getColor());
    }
}
