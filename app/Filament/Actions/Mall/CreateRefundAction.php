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
use Filament\Infolists;
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
                        ->columns()
                        ->schema([
                            Forms\Components\Radio::make('refund_type')
                                ->label('退款类型')
                                ->options(RefundType::class)
                                ->default(RefundType::ReturnRefund)
                                ->required()
                                ->live()
                                ->columnSpanFull()
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
                        ->columns()
                        ->schema([
                            Infolists\Components\TextEntry::make('amount')
                                ->label('订单金额')
                                ->money('cny'),
                            Infolists\Components\TextEntry::make('freight')
                                ->label('运费')
                                ->money('cny'),
                            Forms\Components\TextInput::make('refund_amount')
                                ->label('退款金额')
                                ->required()
                                ->numeric()
                                ->minValue(0.01)
                                ->maxValue(fn (Order $record): string => $record->total_amount)
                                ->suffix('元')
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
                            Forms\Components\Repeater\TableColumn::make('退款')
                                ->width('80px'),
                            Forms\Components\Repeater\TableColumn::make('商品'),
                            Forms\Components\Repeater\TableColumn::make('单价')
                                ->width('100px'),
                            Forms\Components\Repeater\TableColumn::make('可退数量')
                                ->width('100px'),
                            Forms\Components\Repeater\TableColumn::make('退款数量')
                                ->width('100px')
                                ->markAsRequired(),
                            Forms\Components\Repeater\TableColumn::make('备注说明'),
                        ])
                        ->schema([
                            Forms\Components\Hidden::make('order_item_id'),
                            Forms\Components\Checkbox::make('selected')
                                ->live()
                                ->afterStateUpdated(fn (Get $get, Set $set) => self::calculateRefundAmount($get, $set)),
                            Infolists\Components\TextEntry::make('orderable_name'),
                            Infolists\Components\TextEntry::make('price')
                                ->money('cny'),
                            Infolists\Components\TextEntry::make('max_qty')
                                ->alignCenter(),
                            Forms\Components\TextInput::make('qty')
                                ->integer()
                                ->required()
                                ->live()
                                ->afterStateUpdated(fn (Get $get, Set $set) => self::calculateRefundAmount($get, $set)),
                            Forms\Components\TextInput::make('remark'),
                        ])
                        ->default(fn (Order $record) => $record->items()
                            ->with('orderable')
                            ->get()
                            ->map(fn (OrderItem $item) => [
                                'selected' => true,
                                'order_item_id' => $item->id,
                                'orderable_name' => $item->orderable->getOrderableName(),
                                'qty' => $item->qty,
                                'price' => $item->price,
                                'max_qty' => $item->getMaxRefundCounts(),
                            ])
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

    /**
     * 计算退款金额
     *
     * 根据选中的商品和数量计算退款总额，并更新 refund_amount 字段。
     */
    private static function calculateRefundAmount(Get $get, Set $set): void
    {
        $items = $get('items') ?? [];
        $totalAmount = '0.00';

        foreach ($items as $item) {
            if ($item['selected'] ?? false) {
                $qty = $item['qty'] ?? 0;
                $price = $item['price'] ?? 0;
                $totalAmount = bcadd($totalAmount, bcmul($qty, $price, 2), 2);
            }
        }

        $set('refund_amount', $totalAmount);
    }
}
