<?php

namespace App\Enums\Mall;

use App\Enums\Traits\HasStateMachine;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * 订单状态枚举
 *
 * 状态流转图：
 *
 * mail（邮寄）：
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
 */
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
     * 获取可流转至当前状态的前置状态列表
     *
     * @param  FulfillmentType|null  $fulfillmentType  订单履约方式（Completed 的前置状态随履约方式不同）
     *
     * @return static[] 前置状态列表
     */
    public function previous(?FulfillmentType $fulfillmentType = null): array
    {
        return match ($this) {
            self::Canceled => [self::Pending],
            self::Paid => [self::Pending],
            self::Preparing => [self::Paid],
            self::PartiallyShipped => [self::Paid, self::Preparing],
            self::Delivered => [self::Paid, self::Preparing, self::PartiallyShipped],
            self::Signed => [self::PartiallyShipped, self::Delivered],
            self::Completed => match ($fulfillmentType) {
                FulfillmentType::Pickup => [self::Verified],
                FulfillmentType::Virtual => [self::Paid],
                default => [self::Signed],
            },
            self::PickupPending => [self::Paid],
            self::Verified => [self::PickupPending],
            default => [],
        };
    }

    /**
     * 获取当前状态可流转至的后继状态列表
     *
     * @param  FulfillmentType|null  $fulfillmentType  订单履约方式（Paid 的后继状态随履约方式分流）
     *
     * @return static[] 后继状态列表
     */
    public function next(?FulfillmentType $fulfillmentType = null): array
    {
        return match ($this) {
            self::Pending => [self::Canceled, self::Paid],
            self::Paid => match ($fulfillmentType) {
                FulfillmentType::Pickup => [self::PickupPending],
                FulfillmentType::Virtual => [self::Completed],
                default => [self::Preparing, self::PartiallyShipped, self::Delivered],
            },
            self::Preparing => [self::PartiallyShipped, self::Delivered],
            self::PartiallyShipped => [self::Delivered, self::Signed],
            self::Delivered => [self::Signed],
            self::Signed => [self::Completed],
            self::PickupPending => [self::Verified],
            self::Verified => [self::Completed],
            default => [],
        };
    }
}
