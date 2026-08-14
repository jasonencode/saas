<?php

namespace App\Filament\Actions\Mall;

use App\Enums\Mall\OrderStatus;
use App\Models\Mall\Order;
use App\Services\Mall\OrderService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Throwable;

class OrderVerifyAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'orderVerify';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('核销');
        $this->icon(Heroicon::OutlinedCheckBadge);

        $this->visible(fn (Order $order): bool => userCan(self::getDefaultName(), $order) && $this->isVerifiable($order));

        $this->modalWidth(Width::Large);

        $this->schema([
            TextInput::make('pickup_code')
                ->label('核销码')
                ->required()
                ->maxLength(32)
                ->placeholder('请输入核销码'),
        ]);

        $this->action(function (Order $order, array $data): void {
            try {
                service(OrderService::class)
                    ->verify($order, Filament::auth()->user(), $data['pickup_code']);

                $this->successNotificationTitle('核销成功');
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle($e->getMessage());
                $this->failure();
            }
        });
    }

    protected function isVerifiable(Order $order): bool
    {
        return $order->status === OrderStatus::PickupPending;
    }
}
