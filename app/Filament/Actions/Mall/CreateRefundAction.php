<?php

namespace App\Filament\Actions\Mall;

use App\Enums\Mall\RefundReason;
use App\Enums\Mall\RefundType;
use App\Models\Mall\Order;
use App\Models\Mall\OrderItem;
use App\Services\Mall\DTOs\RefundData;
use App\Services\Mall\DTOs\RefundItemData;
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
use Illuminate\Support\HtmlString;
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
                        ->columns(3)
                        ->schema([
                            Infolists\Components\TextEntry::make('total_amount')
                                ->label('订单总额')
                                ->money('cny')
                                ->state(fn (Order $record): string => $record->total_amount),
                            Infolists\Components\TextEntry::make('goods_amount')
                                ->label('商品金额')
                                ->money('cny')
                                ->state(fn (Order $record): string => $record->amount),
                            Infolists\Components\TextEntry::make('freight')
                                ->label('运费')
                                ->money('cny'),
                            Forms\Components\TextInput::make('refund_freight_amount')
                                ->label('退运费')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->maxValue(fn (Order $record): string => $record->freight)
                                ->prefix('￥')
                                ->suffix('元')
                                ->default(fn (Order $record): string => $record->freight)
                                ->live()
                                ->afterStateUpdated(function (Get $get, Set $set): void {
                                    self::calculateRefundAmount($get, $set);
                                }),
                            Forms\Components\TextInput::make('refund_amount')
                                ->label('退款总额')
                                ->readOnly()
                                ->prefix('￥')
                                ->suffix('元')
                                ->default(fn (Order $record): string => $record->total_amount),
                        ]),
                ]),
            Schemas\Components\Section::make('退款商品')
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->hiddenLabel()
                        ->label('退款商品')
                        ->reorderable(false)
                        ->deletable(false)
                        ->addable(false)
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set): void {
                            self::calculateRefundAmount($get, $set);
                        })
                        ->table([
                            Forms\Components\Repeater\TableColumn::make(
                                new HtmlString(<<<'HTML'
                                <input type="checkbox" checked class="fi-checkbox-input" x-data x-on:change="
                                    const checked = $el.checked;

                                    document.querySelectorAll('[data-refund-select]').forEach((cb) => {
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
                                ->extraInputAttributes(['data-refund-select' => true])
                                ->disabled(fn (Get $get): bool => ($get('qty') ?? 0) <= 0),
                            Infolists\Components\TextEntry::make('orderable_name'),
                            Infolists\Components\TextEntry::make('price')
                                ->money('cny'),
                            Infolists\Components\TextEntry::make('max_qty')
                                ->alignCenter(),
                            Forms\Components\TextInput::make('qty')
                                ->integer()
                                ->required()
                                ->minValue(0)
                                ->live()
                                ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                                    if (($state ?? 0) <= 0) {
                                        $set('selected', false);
                                    }
                                }),
                            Forms\Components\TextInput::make('remark'),
                        ])
                        ->default(fn (Order $record) => $record->items()
                            ->with('orderable')
                            ->get()
                            ->map(fn (OrderItem $item) => [
                                'selected' => true,
                                'order_item_id' => $item->id,
                                'orderable_name' => $item->orderable->getOrderableName(),
                                'qty' => $item->getMaxRefundCounts(),
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
                    $this->failureNotificationTitle('请至少选择一个退款商品');
                    $this->failure();

                    return;
                }

                $refundData = RefundData::make(
                    type: $data['refund_type'],
                    reason: RefundReason::from($data['reason']),
                    reasonDetail: $data['reason_detail'] ?? null,
                    items: collect($selectedItems)
                        ->map(fn (array $item): RefundItemData => RefundItemData::make(
                            orderItemId: (int) $item['order_item_id'],
                            qty: (int) $item['qty'],
                            remark: $item['remark'] ?? null,
                        ))
                        ->all(),
                    freightAmount: (string) ($data['refund_freight_amount'] ?? '0.00'),
                );

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
        $goodsAmount = '0.00';

        $orderItemIds = collect($items)
            ->filter(fn ($item) => $item['selected'] ?? false)
            ->pluck('order_item_id')
            ->toArray();

        if (!empty($orderItemIds)) {
            $prices = OrderItem::whereIn('id', $orderItemIds)
                ->pluck('price', 'id');

            foreach ($items as $item) {
                if ($item['selected'] ?? false) {
                    $qty = $item['qty'] ?? 0;
                    $price = $prices[$item['order_item_id']] ?? '0.00';
                    $goodsAmount = bcadd($goodsAmount, bcmul($qty, $price, 2), 2);
                }
            }
        }

        $freightAmount = $get('refund_freight_amount') ?? '0.00';
        $totalAmount = bcadd($goodsAmount, $freightAmount, 2);

        $set('refund_amount', $totalAmount);
    }
}
