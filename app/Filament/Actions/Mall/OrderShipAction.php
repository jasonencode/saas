<?php

namespace App\Filament\Actions\Mall;

use App\Enums\OrderStatus;
use App\Models\Express;
use App\Models\Order;
use App\Models\StoreConfigure;
use App\Services\OrderService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class OrderShipAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'orderShip';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('订单发货');
        $this->icon(Heroicon::OutlinedTruck);
        $this->modalWidth(Width::Large);
        $this->visible(fn (Order $order) => userCan('ship', $order) && $order->items()->whereNull('order_shipping_id')->exists() &&
            in_array($order->status, [OrderStatus::Paid, OrderStatus::Preparing, OrderStatus::PartiallyShipped], true));
        $this->schema([
            Forms\Components\CheckboxList::make('item_ids')
                ->label('选择发货商品')
                ->searchable()
                ->bulkToggleable()
                ->options(
                    fn (Order $order) => $order->items()
                        ->whereNull('order_shipping_id')
                        ->get()
                        ->mapWithKeys(fn ($item) => [
                            $item->id => sprintf('%s x %d', $item->product->name, $item->qty),
                        ])
                )
                ->required(),
            Forms\Components\Select::make('express_id')
                ->label('发货物流')
                ->options(fn () => Express::ofEnabled()->pluck('name', 'id'))
                ->default(fn () => StoreConfigure::whereBelongsTo(Filament::getTenant())->value('default_express_id'))
                ->required(),
            Forms\Components\TextInput::make('express_no')
                ->label('物流单号')
                ->required(),
        ]);
        $this->action(function (Order $order, array $data, OrderService $service) {
            $service->deliver(
                $order,
                $data['item_ids'],
                $data['express_id'],
                $data['express_no'],
                Filament::auth()->user()
            );

            $this->successNotificationTitle('发货成功');
            $this->success();
        });
    }
}
