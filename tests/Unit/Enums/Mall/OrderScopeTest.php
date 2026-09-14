<?php

namespace Tests\Unit\Enums\Mall;

use App\Enums\Mall\OrderScope;
use PHPUnit\Framework\TestCase;

class OrderScopeTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('pending', OrderScope::Pending->value);
        $this->assertSame('ready_to_ship', OrderScope::ReadyToShip->value);
        $this->assertSame('awaiting_receipt', OrderScope::AwaitingReceipt->value);
        $this->assertSame('finished', OrderScope::Finished->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(OrderScope::Pending, OrderScope::from('pending'));
        $this->assertSame(OrderScope::ReadyToShip, OrderScope::from('ready_to_ship'));
        $this->assertSame(OrderScope::AwaitingReceipt, OrderScope::from('awaiting_receipt'));
        $this->assertSame(OrderScope::Finished, OrderScope::from('finished'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(OrderScope::tryFrom('invalid'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(4, OrderScope::cases());
    }

    public function test_pending_label_and_color(): void
    {
        $this->assertSame('待付款', OrderScope::Pending->getLabel());
        $this->assertSame('amber', OrderScope::Pending->getColor());
    }

    public function test_ready_to_ship_label_and_color(): void
    {
        $this->assertSame('待发货', OrderScope::ReadyToShip->getLabel());
        $this->assertSame('blue', OrderScope::ReadyToShip->getColor());
    }

    public function test_awaiting_receipt_label_and_color(): void
    {
        $this->assertSame('待收货', OrderScope::AwaitingReceipt->getLabel());
        $this->assertSame('indigo', OrderScope::AwaitingReceipt->getColor());
    }

    public function test_finished_label_and_color(): void
    {
        $this->assertSame('已完成', OrderScope::Finished->getLabel());
        $this->assertSame('emerald', OrderScope::Finished->getColor());
    }
}
