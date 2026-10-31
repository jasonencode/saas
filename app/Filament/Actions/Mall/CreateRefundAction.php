<?php

namespace App\Filament\Actions\Mall;

use App\Enums\Mall\FulfillmentType;
use App\Enums\Mall\OrderStatus;
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

        $this->label('代用户退款');
        $this->icon(Heroicon::OutlinedArrowUturnLeft);
        $this->color('danger');

        $this->modalWidth(Width::Container);

        $this->visible(function (Order $order): bool {
            $refundService = service(RefundService::class);

            return userCan(self::getDefaultName(), $order)
                && $refundService->isOrderRefundableByOperator($order);
        });

        $this->modalHeading('代用户退款');
        $this->modalDescription('以商家身份代用户创建退款单，退款数量与金额仍按订单可退规则严格校验。');

        $this->schema([
            Schemas\Components\Grid::make()
                ->schema([
                    Schemas\Components\Section::make('退款信息')
                        ->columns()
                        ->schema([
                            Forms\Components\Radio::make('refund_type')
                                ->label('退款类型')
                                ->options(fn (Order $record): array => collect(service(RefundService::class)->getOperatorRefundTypes($record))
                                    ->mapWithKeys(fn (RefundType $type): array => [$type->value => $type->getLabel()])
                                    ->all())
                                ->default(fn (Order $record): RefundType => $record->fulfillment_type === FulfillmentType::Virtual
                                    || ($record->fulfillment_type === FulfillmentType::Pickup && $record->status === OrderStatus::PickupPending)
                                        ? RefundType::OnlyRefund
                                        : RefundType::ReturnRefund)
                                ->required()
                                ->live()
                                ->columnSpanFull()
                                ->afterStateUpdated(function (Get $get, Set $set, Order $record): void {
                                    $set('reason', null);
                                    $set('reason_detail', null);
                                    // 仅退款固定退还剩余运费，切换类型时回到剩余值，避免界面显示与实际退款不一致
                                    $set('refund_freight_amount', $record->getRemainingRefundableFreight());
                                    self::calculateRefundAmount($get, $set);
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
                                ->maxValue(fn (Order $record): string => $record->getRemainingRefundableFreight())
                                ->prefix('￥')
                                ->suffix('元')
                                ->default(fn (Order $record): string => $record->getRemainingRefundableFreight())
                                ->live()
                                ->readOnly(fn (Get $get): bool => in_array($get('refund_type'), [RefundType::OnlyRefund, RefundType::OnlyRefund->value], true))
                                ->helperText(fn (Get $get): ?string => in_array($get('refund_type'), [RefundType::OnlyRefund, RefundType::OnlyRefund->value], true)
                                    ? '仅退款默认退还剩余全部运费'
                                    : null)
                                ->afterStateUpdated(function (Get $get, Set $set): void {
                                    self::calculateRefundAmount($get, $set);
                                }),
                            Forms\Components\TextInput::make('refund_amount')
                                ->label('退款总额')
                                ->readOnly()
                                ->prefix('￥')
                                ->suffix('元')
                                ->default(fn (Order $record): string => self::defaultRefundAmount($record)),
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
                                ->maxValue(fn (Get $get): int => max(0, (int) ($get('max_qty') ?? 0)))
                                ->live()
                                ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                                    if (($state ?? 0) <= 0) {
                                        $set('selected', false);
                                    }
                                }),
                            Forms\Components\TextInput::make('remark'),
                        ])
                        ->default(fn (Order $record) => $record->items()
                            ->with(['orderable', 'refundItems.refund'])
                            ->get()
                            ->map(function (OrderItem $item): array {
                                $maxQty = $item->getMaxRefundCounts();

                                return [
                                    'selected' => $maxQty > 0,
                                    'order_item_id' => $item->id,
                                    'orderable_name' => $item->orderable->getOrderableName(),
                                    'qty' => $maxQty,
                                    'price' => $item->price,
                                    'max_qty' => $maxQty,
                                ];
                            })
                        ),
                ]),
        ]);

        $this->action(function (Order $order, array $data): void {
            try {
                $selectedItems = collect($data['items'])
                    ->filter(fn ($item) => ($item['selected'] ?? false) && (int) ($item['qty'] ?? 0) > 0)
                    ->values()
                    ->toArray();

                if (empty($selectedItems)) {
                    $this->failureNotificationTitle('请至少选择一个退款商品');
                    $this->failure();

                    return;
                }

                $refundType = $data['refund_type'] instanceof RefundType
                    ? $data['refund_type']
                    : RefundType::from((string) $data['refund_type']);

                $reason = $data['reason'] instanceof RefundReason
                    ? $data['reason']
                    : RefundReason::from((string) $data['reason']);

                $refundData = RefundData::make(
                    type: $refundType,
                    reason: $reason,
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
                    ->createRefundForOperator($order, Filament::auth()->user(), $refundData);

                $this->successNotificationTitle('退款单创建成功');
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle('创建退款单失败');
                $this->failureNotificationBody($e->getMessage());
                $this->failure();
            }
        });
    }

    /**
     * 计算退款金额（预览）
     *
     * 与 RefundActions\Apply 保持同一口径：按订单项券后净额、剩余可退金额计算，
     * 避免前端预览金额与实际建单金额不一致。
     */
    private static function calculateRefundAmount(Get $get, Set $set): void
    {
        $items = collect($get('items') ?? [])
            ->filter(fn ($item): bool => ($item['selected'] ?? false) && (int) ($item['qty'] ?? 0) > 0);

        $goodsAmount = '0.00';

        if ($items->isNotEmpty()) {
            $orderItems = OrderItem::whereIn('id', $items->pluck('order_item_id')->map(static fn ($id): int => (int) $id)->all())
                ->get(['id', 'price', 'qty', 'coupon_discount'])
                ->keyBy(static fn (OrderItem $item): int => (int) $item->getKey());

            foreach ($items as $row) {
                $orderItem = $orderItems->get((int) $row['order_item_id']);

                if (!$orderItem) {
                    continue;
                }

                $goodsAmount = bcadd(
                    $goodsAmount,
                    self::netItemRefundAmount($orderItem, (int) $row['qty'], (int) ($row['max_qty'] ?? 0)),
                    2,
                );
            }
        }

        $freightAmount = $get('refund_freight_amount') ?? '0.00';

        $set('refund_amount', bcadd($goodsAmount, $freightAmount, 2));
    }

    /**
     * 默认退款总额（全部可退商品 + 剩余可退运费）
     */
    private static function defaultRefundAmount(Order $record): string
    {
        $record->loadMissing('items.refundItems.refund');

        $goodsAmount = '0.00';

        foreach ($record->items as $item) {
            $maxQty = $item->getMaxRefundCounts();

            if ($maxQty < 1) {
                continue;
            }

            $goodsAmount = bcadd($goodsAmount, self::netItemRefundAmount($item, $maxQty, $maxQty), 2);
        }

        return bcadd($goodsAmount, $record->getRemainingRefundableFreight(), 2);
    }

    /**
     * 单个订单项的券后退款金额
     *
     * @param  OrderItem  $item  订单项
     * @param  int  $refundQty  本次退款数量
     * @param  int  $maxQty  剩余可退数量
     */
    private static function netItemRefundAmount(OrderItem $item, int $refundQty, int $maxQty): string
    {
        $purchasedQty = (int) $item->qty;

        if ($purchasedQty < 1 || $refundQty < 1) {
            return '0.00';
        }

        $netAmount = bcsub(
            bcmul((string) $item->price, (string) $purchasedQty, 2),
            (string) $item->coupon_discount,
            2,
        );

        $unitPrice = number_format((float) bcdiv($netAmount, (string) $purchasedQty, 4), 2, '.', '');
        $refundedQty = max(0, $purchasedQty - $maxQty);
        $remainingAmount = bcsub($netAmount, bcmul($unitPrice, (string) $refundedQty, 2), 2);
        $itemAmount = bcmul($unitPrice, (string) $refundQty, 2);

        return bccomp($itemAmount, $remainingAmount, 2) === 1 ? $remainingAmount : $itemAmount;
    }
}
