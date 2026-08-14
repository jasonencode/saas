<?php

namespace App\Enums\Mall;

use App\Enums\Traits\HasStateMachine;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasColor, HasLabel
{
    use HasStateMachine;

    /**
     * 订单初始化：用户已下单，未付款
     */
    case Pending = 'pending';

    /**
     * 订单取消：用户未支付并取消订单，超时未支付后自动取消订单
     */
    case Canceled = 'canceled';

    /**
     * 已支付：用户付款完成，等待发货（自提/虚拟商品由此分流）
     */
    case Paid = 'paid';

    /**
     * 备货中：打印订单、拣货、打包
     */
    case Preparing = 'preparing';

    /**
     * 部分发货：部分商品已发货
     */
    case PartiallyShipped = 'partially';

    /**
     * 已发货：卖家已发货
     */
    case Delivered = 'delivered';

    /**
     * 已签收：用户已签收
     */
    case Signed = 'signed';

    /**
     * 已完成：用户签收/核销 N 天后，完成订单，不再做任何操作
     */
    case Completed = 'completed';

    /**
     * 待自提：门店自提订单付款后进入，等待用户到店核销
     */
    case PickupPending = 'pickup_pending';

    /**
     * 已核销：商家核销通过，等价于 mail 链路的「已签收」
     */
    case Verified = 'verified';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => '待付款',
            self::Canceled => '已取消',
            self::Paid => '待发货',
            self::Preparing => '备货中',
            self::PartiallyShipped => '部分发货',
            self::Delivered => '已发货',
            self::Signed => '已签收',
            self::Completed => '已完成',
            self::PickupPending => '待自提',
            self::Verified => '已核销',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Canceled => 'red',
            self::Paid => 'blue',
            self::Preparing => 'sky',
            self::PartiallyShipped => 'cyan',
            self::Delivered => 'indigo',
            self::Signed => 'teal',
            self::Completed => 'emerald',
            self::PickupPending => 'amber',
            self::Verified => 'teal',
        };
    }

    /**
     * 订单状态流转图：
     *
     * mail（现有链路）：
     *   Pending ──→ Canceled（终态）
     *      │
     *      └──→ Paid ──→ Preparing ──→ PartiallyShipped ──→ Delivered ──→ Signed ──→ Completed（终态）
     *             │                         │                    │
     *             └─────────────────────────┴────────────────────┘
     *                    （可跳级发货/部分发货）
     *
     * pickup（门店自提）：
     *   Pending ──→ Canceled
     *      │
     *      └──→ Paid ──→ PickupPending ──→ Verified ──→ Completed（终态）
     *
     * virtual（虚拟商品）：
     *   Pending ──→ Canceled
     *      │
     *      └──→ Paid ──→ Completed（终态）
     *
     * @param  FulfillmentType|null  $fulfillmentType  订单履约方式（Paid 状态分流用）
     *
     * @return static[]
     */
    public function previous(?FulfillmentType $fulfillmentType = null): array
    {
        return match ($this) {
            self::Paid => [self::Pending],
            self::Canceled => [self::Pending],
            self::Preparing => [self::Paid],
            self::PartiallyShipped => [self::Paid, self::Preparing],
            self::Delivered => [self::Paid, self::Preparing, self::PartiallyShipped],
            self::Signed => [self::Delivered, self::PartiallyShipped],
            self::PickupPending => [self::Paid],
            self::Verified => [self::PickupPending],
            self::Completed => match ($fulfillmentType) {
                FulfillmentType::Pickup => [self::Verified],
                FulfillmentType::Virtual => [self::Paid],
                default => [self::Signed],
            },
            default => [],
        };
    }

    /**
     * @param  FulfillmentType|null  $fulfillmentType  订单履约方式（Paid 状态分流用）
     *
     * @return static[]
     */
    public function next(?FulfillmentType $fulfillmentType = null): array
    {
        return match ($this) {
            self::Pending => [self::Canceled, self::Paid],
            self::Paid => match ($fulfillmentType) {
                FulfillmentType::Pickup => [self::PickupPending],
                FulfillmentType::Virtual => [self::Completed],
                default => [self::Preparing, self::Delivered, self::PartiallyShipped],
            },
            self::Preparing => [self::Delivered, self::PartiallyShipped],
            self::PartiallyShipped => [self::Delivered, self::Signed],
            self::Delivered => [self::Signed],
            self::Signed => [self::Completed],
            self::PickupPending => [self::Verified],
            self::Verified => [self::Completed],
            default => [],
        };
    }
}
