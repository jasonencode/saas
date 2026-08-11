<?php

namespace App\Filament\Actions\Mall;

use App\Enums\Mall\RefundReason;
use App\Enums\Mall\RefundType;
use App\Models\Mall\Order;
use App\Models\Mall\OrderItem;
use App\Services\Mall\RefundService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Throwable;

class CreateRefundAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'createRefund';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('创建退款单');
        $this->icon(Heroicon::OutlinedArrowUturnLeft);
        $this->color('danger');

        $this->modalWidth(Width::Container);

        $this->visible(function (Order $order): bool {
            $refundService = service(RefundService::class);

            return userCan(self::getDefaultName(), $order)
                && $refundService->isOrderRefundable($order);
        });

        $this->modalHeading('创建退款单');
        $this->modalDescription('确定要为此订单创建退款单吗？');

        $this->schema([
            Schemas\Components\Grid::make()
                ->schema([
                    Schemas\Components\Section::make('退款信息')
                        ->schema([
                            Forms\Components\Select::make('refund_type')
                                ->label('退款类型')
                                ->options(RefundType::class)
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Set $set): void {
                                    $set('reason', null);
                                    $set('reason_detail', null);
                                }),
                            Forms\Components\Select::make('reason')
                                ->label('退款原因')
                                ->options(fn (Get $get): array => match ($get('refund_type')) {
                                    RefundType::OnlyRefund => RefundType::OnlyRefund->reasons(),
                                    RefundType::ReturnRefund => RefundType::ReturnRefund->reasons(),
                                    default => [],
                                })
                                ->required()
                                ->live()
                                ->visible(fn (Get $get): bool => filled($get('refund_type'))),
                            Forms\Components\TextInput::make('reason_detail')
                                ->label('原因详情')
                                ->maxLength(500)
                                ->visible(fn (Get $get): bool => $get('reason') === RefundReason::Other->value)
                                ->required(fn (Get $get): bool => $get('reason') === RefundReason::Other->value),
                        ]),
                    Schemas\Components\Section::make('退款金额')
                        ->schema([
                            Forms\Components\TextInput::make('refund_amount')
                                ->label('退款金额')
                                ->required()
                                ->numeric()
                                ->minValue(0.01)
                                ->maxValue(fn (Order $record): string => $record->total_amount)
                                ->suffix('元')
                                ->helperText(fn (Order $record): string => "订单总额: ¥{$record->total_amount} (含运费: ¥{$record->freight})")
                                ->default(fn (Order $record): string => $record->total_amount),
                        ]),
                ]),
            Schemas\Components\Section::make('退款商品')
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->label('退款商品')
                        ->reorderable(false)
                        ->deletable(false)
                        ->addable(false)
                        ->table([
                            Forms\Components\Repeater\TableColumn::make('退款'),
                            Forms\Components\Repeater\TableColumn::make('商品'),
                            Forms\Components\Repeater\TableColumn::make('单价')
                                ->width('150px'),
                            Forms\Components\Repeater\TableColumn::make('可退数量')
                                ->width('150px'),
                            Forms\Components\Repeater\TableColumn::make('退款数量')
                                ->width('150px'),
                            Forms\Components\Repeater\TableColumn::make('备注说明'),
                        ])
                        ->schema([
                            Forms\Components\Hidden::make('order_item_id'),
                            Forms\Components\Checkbox::make('selected')
                                ->default(true),
                            TextEntry::make('orderable_name'),
                            Forms\Components\TextInput::make('price'),
                            Forms\Components\TextInput::make('max_qty')
                                ->readOnly(),
                            Forms\Components\TextInput::make('qty')
                                ->default(1),
                            Forms\Components\TextInput::make('remark'),
                        ])
                        ->default(fn (Order $record): array => $record->items()
                            ->with('orderable')
                            ->get()
                            ->map(fn (OrderItem $item) => [
                                'selected' => true,
                                'order_item_id' => (string) $item->id,
                                'orderable_name' => $item->orderable_name,
                                'qty' => $item->qty,
                                'price' => $item->price,
                                'max_qty' => $item->qty,
                            ])
                            ->toArray()
                        ),
                ]),
        ]);

        $this->action(function (Order $order, array $data): void {
            try {
                $selectedItems = collect($data['items'])
                    ->filter(fn ($item) => $item['selected'] ?? false)
                    ->values()
                    ->toArray();

                if (empty($selectedItems)) {
                    throw new \InvalidArgumentException('请至少选择一个退款商品');
                }

                $refundData = [
                    'type' => $data['refund_type'],
                    'reason' => $data['reason'],
                    'reason_detail' => $data['reason_detail'] ?? null,
                    'refund_amount' => $data['refund_amount'],
                    'items' => collect($selectedItems)->map(fn ($item) => [
                        'order_item_id' => $item['order_item_id'],
                        'qty' => $item['qty'],
                        'price' => $item['price'],
                    ])->toArray(),
                ];

                service(RefundService::class)
                    ->createRefund($order, Filament::auth()->user(), $refundData);

                $this->successNotificationTitle('退款单创建成功');
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle($e->getMessage());
                $this->failure();
            }
        });
    }
}
