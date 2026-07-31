<?php

namespace App\Filament\Actions\Mall;

use App\Enums\Mall\OrderStatus;
use App\Models\Mall\Express;
use App\Models\Mall\Order;
use App\Models\Mall\StoreConfigure;
use App\Services\Mall\OrderService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Throwable;

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

        $this->visible(fn (Order $order): bool => userCan(self::getDefaultName(), $order) && $this->hasUnshippedItems($order) && $this->isShippableStatus($order));

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
        $this->action(function (Order $order, array $data, OrderService $service): void {
            try {
                $service->deliver(
                    order: $order,
                    itemIds: $data['item_ids'],
                    expressId: $data['express_id'],
                    expressNo: $data['express_no'],
                    user: Filament::auth()->user()
                );

                $this->successNotificationTitle('发货成功');
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle($e->getMessage());
                $this->failure();
            }
        });
    }

    protected function isShippableStatus(Order $order): bool
    {
        return in_array($order->status, [
            OrderStatus::Paid,
            OrderStatus::Preparing,
            OrderStatus::PartiallyShipped,
        ], true);
    }

    protected function hasUnshippedItems(Order $order): bool
    {
        return $order->items()->whereNull('order_shipping_id')->exists();
    }
}
