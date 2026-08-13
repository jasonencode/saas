<?php

namespace App\Filament\Actions\Mall;

use App\Enums\Mall\OrderStatus;
use App\Models\Mall\Express;
use App\Models\Mall\Order;
use App\Models\Mall\OrderItem;
use App\Models\Mall\StoreConfigure;
use App\Services\Mall\OrderService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Infolists;
use Filament\Schemas\Components\Section;
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
        $this->modalWidth(Width::ThreeExtraLarge);

        $this->visible(fn (Order $order): bool => userCan(self::getDefaultName(), $order) && $this->hasUnshippedItems($order) && $this->isShippableStatus($order));

        $this->schema([
            Section::make('发货商品')
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->label('发货商品')
                        ->reorderable(false)
                        ->deletable(false)
                        ->addable(false)
                        ->hiddenLabel()
                        ->table([
                            Forms\Components\Repeater\TableColumn::make('发货')
                                ->width('40px')
                                ->hiddenHeaderLabel(),
                            Forms\Components\Repeater\TableColumn::make('商品'),
                            Forms\Components\Repeater\TableColumn::make('单价')
                                ->width('100px'),
                            Forms\Components\Repeater\TableColumn::make('数量')
                                ->width('100px'),
                        ])
                        ->schema([
                            Forms\Components\Hidden::make('order_item_id'),
                            Forms\Components\Checkbox::make('selected')
                                ->default(true),
                            Infolists\Components\TextEntry::make('orderable_name'),
                            Infolists\Components\TextEntry::make('price')
                                ->money('cny'),
                            Infolists\Components\TextEntry::make('qty')
                                ->alignCenter(),
                        ])
                        ->default(fn (Order $record) => $record->items()
                            ->whereNull('order_shipping_id')
                            ->with('orderable')
                            ->get()
                            ->map(fn (OrderItem $item) => [
                                'selected' => true,
                                'order_item_id' => $item->id,
                                'orderable_name' => $item->orderable?->getOrderableName(),
                                'price' => $item->price,
                                'qty' => $item->qty,
                            ])
                        ),
                ]),
            Section::make('发货信息')
                ->columns()
                ->schema([
                    Forms\Components\Select::make('express_id')
                        ->label('发货物流')
                        ->options(fn () => Express::ofEnabled()->bySort()->pluck('name', 'id'))
                        ->default(fn () => StoreConfigure::whereBelongsTo(Filament::getTenant())->value('default_express_id'))
                        ->required(),
                    Forms\Components\TextInput::make('express_no')
                        ->label('物流单号')
                        ->required(),
                ]),
        ]);

        $this->action(function (Order $order, OrderService $service, array $data): void {
            try {
                $itemIds = collect($data['items'])
                    ->filter(fn ($item) => $item['selected'] ?? false)
                    ->pluck('order_item_id')
                    ->values()
                    ->all();

                if (empty($itemIds)) {
                    $this->failureNotificationTitle('请至少选择一个发货商品');
                    $this->failure();
                    $this->halt();
                }

                $service->deliver(
                    order: $order,
                    itemIds: $itemIds,
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
