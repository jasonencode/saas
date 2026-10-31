<?php

namespace App\Filament\Actions\Mall;

use App\Enums\Mall\OrderStatus;
use App\Models\Mall\Order;
use App\Models\Mall\OrderShipping;
use App\Services\Mall\OrderService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Throwable;

class OrderCancelDeliverAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'orderCancelDeliver';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('取消发货');
        $this->icon(Heroicon::OutlinedArrowUturnLeft);
        $this->color('danger');
        $this->modalWidth(Width::Large);

        $this->visible(fn (Order $order): bool => userCan(self::getDefaultName(), $order)
            && in_array($order->status, [OrderStatus::PartiallyShipped, OrderStatus::Delivered], true));

        $this->modalHeading('取消发货');
        $this->modalDescription('取消后商品将退回未发货状态，可重新发货。');

        $this->schema([
            Forms\Components\CheckboxList::make('shipping_ids')
                ->label('要取消的发货记录')
                ->options(fn (Order $order): array => $order->shippings()
                    ->with('express')
                    ->get()
                    ->mapWithKeys(fn (OrderShipping $shipping): array => [
                        $shipping->id => sprintf(
                            '%s / %s',
                            $shipping->express?->name ?? '未指定物流',
                            $shipping->express_no,
                        ),
                    ])
                    ->all())
                ->default(fn (Order $order): array => $order->shippings()->pluck('id')->all())
                ->required()
                ->columns(1),
        ]);

        $this->action(function (Order $order, OrderService $service, array $data): void {
            try {
                $shippings = $order->shippings()
                    ->whereIn('id', $data['shipping_ids'] ?? [])
                    ->get();

                $service->cancelDeliver($order, Filament::auth()->user(), $shippings);

                $this->successNotificationTitle('发货已取消');
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle('取消发货失败');
                $this->failureNotificationBody($e->getMessage());
                $this->failure();
            }
        });
    }
}
