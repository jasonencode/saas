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
use Illuminate\Support\HtmlString;
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
                    Infolists\Components\TextEntry::make('refund_pending_reminder')
                        ->label('退款提醒')
                        ->color('warning')
                        ->state(fn (Order $record): ?HtmlString => $this->getRefundPendingReminder($record))
                        ->visible(fn (Order $record): bool => filled($this->getRefundPendingReminder($record))),
                    Forms\Components\Repeater::make('items')
                        ->label('发货商品')
                        ->reorderable(false)
                        ->deletable(false)
                        ->addable(false)
                        ->hiddenLabel()
                        ->table([
                            Forms\Components\Repeater\TableColumn::make(
                                new HtmlString(<<<'HTML'
                                <input type="checkbox" checked class="fi-checkbox-input" x-data x-on:change="
                                    const checked = $el.checked;

                                    document.querySelectorAll('[data-ship-select]').forEach((cb) => {
                                        cb.checked = checked;
                                        cb.dispatchEvent(new Event('change', { bubbles: true }));
                                    });
                                ">
                                HTML)
                            )
                                ->width('40px'),
                            Forms\Components\Repeater\TableColumn::make('商品'),
                            Forms\Components\Repeater\TableColumn::make('单价')
                                ->width('100px'),
                            Forms\Components\Repeater\TableColumn::make('数量')
                                ->width('100px'),
                        ])
                        ->schema([
                            Forms\Components\Hidden::make('order_item_id'),
                            Forms\Components\Checkbox::make('selected')
                                ->default(true)
                                ->live()
                                ->extraInputAttributes(['data-ship-select' => true]),
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
                            ->reject(fn (OrderItem $item) => $item->getMaxRefundCounts() < $item->qty)
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

                    return;
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
        return $order->items()
            ->whereNull('order_shipping_id')
            ->with('orderable')
            ->get()
            ->contains(fn (OrderItem $item) => $item->getMaxRefundCounts() >= $item->qty);
    }

    /**
     * 获取存在退款申请的商品提醒
     *
     * @return HtmlString|null 提醒文案，无退款申请商品时返回 null
     */
    protected function getRefundPendingReminder(Order $order): ?HtmlString
    {
        $names = $order->items()
            ->whereNull('order_shipping_id')
            ->with('orderable')
            ->get()
            ->filter(fn (OrderItem $item) => $item->getMaxRefundCounts() < $item->qty)
            ->map(fn (OrderItem $item): string => $item->orderable?->getOrderableName().' x '.$item->qty)
            ->filter()
            ->unique();

        if ($names->isEmpty()) {
            return null;
        }

        $items = $names
            ->map(fn (string $name): string => "<div class=\"font-medium\">$name</div>")
            ->implode('');

        return new HtmlString("以下商品存在退款申请，已从发货列表剔除：$items");
    }
}
