<?php

namespace App\Filament\Actions\Mall;

use App\Enums\OrderStatus;
use App\Enums\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\PaymentOrder;
use App\Services\OrderService;
use Exception;
use Filament\Actions\Action;

class OrderVirtualPaymentAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'virtualPaymentAction';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('虚拟付款');
        $this->icon('heroicon-o-banknotes');
        $this->color('success');
        $this->requiresConfirmation();
        $this->modalHeading('确认虚拟付款');
        $this->modalDescription('确定要标记此订单为已付款吗？此操作将直接修改订单状态。');
        $this->modalSubmitActionLabel('确认付款');

        $this->visible(fn (Order $record): bool => $record->status === OrderStatus::Pending);

        $this->action(function (Order $order): void {
            try {
                $paymentOrder = PaymentOrder::create([
                    'tenant_id' => $order->tenant_id,
                    'user_id' => $order->user_id,
                    'paymentable' => $order,
                    'gateway' => PaymentGateway::Manual,
                    'status' => PaymentStatus::Paid,
                    'amount' => $order->getTotalAmount(),
                    'paid_at' => now(),
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);
                resolve(OrderService::class)
                    ->pay($order, $paymentOrder);

                $this->successNotificationTitle('订单已标记为已付款');
                $this->success();
            } catch (Exception $exception) {
                $this->failureNotificationTitle($exception->getMessage());
                $this->failure();
            }
        });
    }
}