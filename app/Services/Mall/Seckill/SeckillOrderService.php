<?php

namespace App\Services\Mall\Seckill;

use App\Contracts\ServiceInterface;
use App\Enums\Campaign\SeckillRecordStatus;
use App\Enums\Mall\FulfillmentType;
use App\Models\Campaign\SeckillSession;
use App\Models\Mall\Order;
use App\Models\User\Address;
use App\Models\User\User;
use App\Services\Mall\DTOs\OrderItemDto;
use App\Services\Mall\OrderService;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * 秒杀下单
 *
 * 流程：预扣名额 → 复用普通订单创建（秒杀价覆盖）→ 绑定记录；
 * 建单失败回补名额，保证名额与订单一致。
 */
class SeckillOrderService implements ServiceInterface
{
    /**
     * @param  User  $user  下单用户
     * @param  SeckillSession  $session  秒杀场次
     * @param  int  $qty  购买数量
     * @param  FulfillmentType  $fulfillmentType  履约方式
     * @param  Address|int|null  $address  收货地址
     * @param  int|null  $pickupPointId  自提点
     * @param  string|null  $remark  买家备注
     *
     * @throws InvalidArgumentException|RuntimeException|Throwable
     */
    public function create(
        User $user,
        SeckillSession $session,
        int $qty,
        FulfillmentType $fulfillmentType = FulfillmentType::Mail,
        Address|int|null $address = null,
        ?int $pickupPointId = null,
        ?string $remark = null,
    ): Order {
        if ($qty < 1) {
            throw new InvalidArgumentException('购买数量必须大于 0');
        }

        $session->loadMissing('sku.product', 'tenant');

        if ($session->sku === null || $session->tenant === null) {
            throw new RuntimeException('秒杀商品不存在');
        }

        $counter = service(RedisSeckillStockCounter::class);
        $reservation = $counter->reserve($session, $user, $qty);

        if (!$reservation->ok) {
            throw new RuntimeException($reservation->message());
        }

        try {
            $order = service(OrderService::class)->createOrder(
                tenant: $session->tenant,
                user: $user,
                items: [
                    OrderItemDto::make(
                        $session->sku,
                        $qty,
                        $remark,
                        number_format((float) $session->seckill_price, 2, '.', ''),
                    ),
                ],
                fulfillmentType: $fulfillmentType,
                address: $address,
                remark: $remark,
                pickupPointId: $pickupPointId,
                seckillSessionId: $session->getKey(),
            );

            service(SeckillReservationService::class)->markOrdered($reservation->record, $order);

            return $order->refresh();
        } catch (Throwable $exception) {
            $counter->release($reservation->record, SeckillRecordStatus::Cancelled);

            throw $exception;
        }
    }
}
