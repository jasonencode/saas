<?php

namespace App\Filament\Actions\Mall;

use App\Enums\Mall\OrderStatus;
use App\Models\Mall\Order;
use App\Services\OrderService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;

class OrderPreparingAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'orderPreparing';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('开始备货');
        $this->icon(Heroicon::OutlinedClipboardDocumentCheck);
        $this->color('sky');
        $this->visible(fn (Order $order) => $order->status === OrderStatus::Paid);
        $this->requiresConfirmation();
        $this->action(function (Order $order, OrderService $service) {
            $service->preparing($order, Filament::auth()->user());

            $this->successNotificationTitle('已进入备货状态');
            $this->success();
        });
    }
}
