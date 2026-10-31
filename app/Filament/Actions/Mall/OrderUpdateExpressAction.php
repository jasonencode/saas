<?php

namespace App\Filament\Actions\Mall;

use App\Enums\Mall\OrderStatus;
use App\Models\Mall\Express;
use App\Models\Mall\Order;
use App\Models\Mall\OrderShipping;
use App\Services\Mall\OrderService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Throwable;

class OrderUpdateExpressAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'orderUpdateExpress';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('修改物流信息');
        $this->icon(Heroicon::OutlinedPencilSquare);
        $this->color('info');
        $this->modalWidth(Width::ThreeExtraLarge);

        $this->visible(fn (Order $order): bool => userCan(self::getDefaultName(), $order)
            && in_array($order->status, [OrderStatus::PartiallyShipped, OrderStatus::Delivered, OrderStatus::Signed], true)
            && $order->shippings()->exists());

        $this->fillForm(fn (Order $order): array => [
            'shippings' => $order->shippings()
                ->get()
                ->map(fn (OrderShipping $shipping): array => [
                    'id' => $shipping->id,
                    'express_id' => $shipping->express_id,
                    'express_no' => $shipping->express_no,
                ])
                ->values()
                ->all(),
        ]);

        $this->schema([
            Forms\Components\Repeater::make('shippings')
                ->label('发货记录')
                ->reorderable(false)
                ->addable(false)
                ->deletable(false)
                ->columns(3)
                ->schema([
                    Forms\Components\Hidden::make('id'),
                    Forms\Components\Select::make('express_id')
                        ->label('物流公司')
                        ->options(fn (): array => Express::ofEnabled()->bySort()->pluck('name', 'id')->all())
                        ->searchable()
                        ->required(),
                    Forms\Components\TextInput::make('express_no')
                        ->label('物流单号')
                        ->required()
                        ->maxLength(32),
                ]),
        ]);

        $this->action(function (Order $order, OrderService $service, array $data): void {
            try {
                $expresses = collect($data['shippings'] ?? [])
                    ->filter(fn (array $row): bool => !empty($row['id']))
                    ->mapWithKeys(fn (array $row): array => [
                        $row['id'] => [
                            'express_id' => $row['express_id'],
                            'express_no' => $row['express_no'],
                        ],
                    ])
                    ->all();

                $service->updateExpress($order, $expresses, Filament::auth()->user());

                $this->successNotificationTitle('物流信息已修改');
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle('修改物流信息失败');
                $this->failureNotificationBody($e->getMessage());
                $this->failure();
            }
        });
    }
}
