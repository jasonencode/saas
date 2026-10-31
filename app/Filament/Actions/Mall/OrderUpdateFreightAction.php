<?php

namespace App\Filament\Actions\Mall;

use App\Enums\Mall\OrderStatus;
use App\Models\Mall\Order;
use App\Services\Mall\OrderService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Support\Icons\Heroicon;
use Throwable;

class OrderUpdateFreightAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'orderUpdateFreight';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('修改运费');
        $this->icon(Heroicon::OutlinedBanknotes);
        $this->color('warning');

        $this->visible(fn (Order $order): bool => userCan(self::getDefaultName(), $order) && $order->status === OrderStatus::Pending);

        $this->fillForm(fn (Order $order): array => [
            'freight' => (float) $order->freight,
        ]);

        $this->schema([
            Forms\Components\TextInput::make('freight')
                ->label('运费')
                ->numeric()
                ->minValue(0)
                ->prefix('￥')
                ->required(),
        ]);

        $this->action(function (Order $order, OrderService $service, array $data): void {
            try {
                $service->updateFreight($order, (float) $data['freight'], Filament::auth()->user());

                $this->successNotificationTitle('运费已修改');
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle('修改运费失败');
                $this->failureNotificationBody($e->getMessage());
                $this->failure();
            }
        });
    }
}
