<?php

namespace App\Filament\Actions\Mall;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class OrderPrintShippingAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'orderPrintShipping';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('打印发货单');
        $this->icon(Heroicon::OutlinedPrinter);
        $this->visible(fn (Order $order) => $order->status === OrderStatus::Paid);
    }
}
