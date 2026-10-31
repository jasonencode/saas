<?php

namespace App\Services\Mall;

use App\Contracts\Authenticatable;
use App\Contracts\ServiceInterface;
use App\Enums\Mall\FulfillmentType;
use App\Models\Mall\Order;
use App\Models\Mall\OrderShipping;
use App\Models\System\Tenant;
use App\Models\User\Address;
use App\Services\Mall\DTOs\OrderItemDto;
use Illuminate\Support\Collection;

/**
 * 订单服务：订单创建、支付与各环节状态流转入口
 *
 * 下单所需数据（用户、商品、地址、备注、优惠券、自提点）全部由调用方显式传入，
 * 服务内部不读取 Auth 或请求上下文；操作人也作为 Authenticatable 显式传入。
 *
 * 门面方法只做参数转交，具体流程见 OrderActions\*：
 * 创建 → 支付 → 备货 → 发货 → 删除发货 → 签收 → 完成 → 自提核销 → 取消 → 删除
 * → 改地址 → 商家备注。
 *
 * 操作日志统一由 System\EventLogService 写入日志中心（event_logs），在动作的 log() 阶段完成。
 */
class OrderService implements ServiceInterface
{
    /**
     * 从购物车创建订单（按租户拆分）
     *
     * @param  Authenticatable  $user  下单用户
     * @param  Collection|array  $items  订单商品列表（OrderItemDto 数组）
     * @param  FulfillmentType  $fulfillmentType  下单所选履约方式，订单内所有商品必须支持
     * @param  Address|int|null  $address  收货地址（地址对象、地址 ID 或 null）
     * @param  int|null  $pickupPointId  自提点 ID（门店自提单必填）
     * @param  int|null  $couponUserId  用户持券实例 ID（优惠券是租户维度，仅抵扣其所属租户的子订单）
     *
     * @throws \RuntimeException|\Throwable 租户不存在或券租户不在本次拆单范围
     * @throws \InvalidArgumentException 订单无商品
     *
     * @return Collection<int, Order> 生成的订单列表
     */
    public function createOrders(
        Authenticatable $user,
        Collection|array $items,
        FulfillmentType $fulfillmentType = FulfillmentType::Mail,
        Address|int|null $address = null,
        ?int $pickupPointId = null,
        ?int $couponUserId = null
    ): Collection {
        return OrderActions\Create::dispatch(
            user: $user,
            items: collect($items)->all(),
            fulfillmentType: $fulfillmentType,
            address: $address,
            pickupPointId: $pickupPointId,
            couponUserId: $couponUserId,
        );
    }

    /**
     * 创建单租户订单
     *
     * @param  Tenant  $tenant  所属租户
     * @param  Authenticatable  $user  下单用户
     * @param  Collection|array  $items  订单商品列表（OrderItemDto 数组）
     * @param  FulfillmentType  $fulfillmentType  下单所选履约方式，订单内所有商品必须支持
     * @param  Address|int|null  $address  收货地址（地址对象、地址 ID 或 null）
     * @param  string|null  $remark  订单备注
     * @param  int|null  $pickupPointId  自提点 ID（门店自提单必填）
     * @param  int|null  $couponUserId  用户持券实例 ID（在订单事务内核销）
     *
     * @throws \InvalidArgumentException 商品列表为空或商品类型错误
     * @throws \RuntimeException 地址不正确、自提点不正确或订单项不支持所选履约方式
     * @throws \Throwable 事务异常
     */
    public function createOrder(
        Tenant $tenant,
        Authenticatable $user,
        Collection|array $items,
        FulfillmentType $fulfillmentType = FulfillmentType::Mail,
        Address|int|null $address = null,
        ?string $remark = null,
        ?int $pickupPointId = null,
        ?int $couponUserId = null
    ): Order {
        return OrderActions\CreateOrder::dispatch(
            tenant: $tenant,
            user: $user,
            items: collect($items)->all(),
            fulfillmentType: $fulfillmentType,
            address: $address,
            remark: $remark,
            pickupPointId: $pickupPointId,
            couponUserId: $couponUserId,
        );
    }

    /**
     * 订单支付成功
     *
     * @param  Order  $order  订单
     * @param  Authenticatable  $user  操作人
     *
     * @throws \Throwable
     */
    public function pay(Order $order, Authenticatable $user): void
    {
        OrderActions\Pay::dispatch($order, $user);
    }

    /**
     * 取消订单
     *
     * @param  Order  $order  订单
     * @param  Authenticatable  $user  操作人
     *
     * @throws \Throwable
     */
    public function cancel(Order $order, Authenticatable $user): void
    {
        OrderActions\Cancel::dispatch($order, $user);
    }

    /**
     * 订单发货
     *
     * @param  Order  $order  订单
     * @param  array  $itemIds  发货的商品明细 ID 列表
     * @param  int  $expressId  快递公司 ID
     * @param  string  $expressNo  快递单号
     * @param  Authenticatable  $user  操作人
     *
     * @throws \Throwable
     */
    public function deliver(Order $order, array $itemIds, int $expressId, string $expressNo, Authenticatable $user): void
    {
        OrderActions\Deliver::dispatch($order, $itemIds, $expressId, $expressNo, $user);
    }

    /**
     * 删除发货记录
     *
     * @param  OrderShipping  $express  发货记录
     * @param  Authenticatable  $user  操作人
     *
     * @throws \Throwable
     */
    public function deleteExpress(OrderShipping $express, Authenticatable $user): void
    {
        OrderActions\DeleteExpress::dispatch($express, $user);
    }

    /**
     * 取消发货（可指定发货记录，null 表示全部取消）
     *
     * @param  Order  $order  订单
     * @param  Authenticatable  $user  操作人
     * @param  Collection<int, OrderShipping>|null  $shippings  指定取消的发货记录，null 表示全部
     *
     * @throws \Throwable 订单状态不允许或无可取消的发货记录
     */
    public function cancelDeliver(Order $order, Authenticatable $user, ?Collection $shippings = null): void
    {
        OrderActions\CancelDeliver::dispatch($order, $user, $shippings);
    }

    /**
     * 修改已发货订单的物流信息
     *
     * @param  Order  $order  订单
     * @param  array<int|string, array{express_id?: int|string, express_no?: string}>  $expresses  以发货记录 ID 为键的新物流信息
     * @param  Authenticatable  $user  操作人
     *
     * @throws \Throwable 订单状态不允许或物流信息不合法
     */
    public function updateExpress(Order $order, array $expresses, Authenticatable $user): void
    {
        OrderActions\UpdateExpress::dispatch($order, $expresses, $user);
    }

    /**
     * 删除订单
     *
     * @param  Order  $order  订单
     * @param  Authenticatable  $user  操作人
     *
     * @throws \InvalidArgumentException 订单状态不允许删除
     * @throws \Throwable
     */
    public function delete(Order $order, Authenticatable $user): void
    {
        OrderActions\Delete::dispatch($order, $user);
    }

    /**
     * 订单签收
     *
     * @param  Order  $order  订单
     * @param  Authenticatable  $user  操作人
     *
     * @throws \Throwable
     */
    public function sign(Order $order, Authenticatable $user): void
    {
        OrderActions\Sign::dispatch($order, $user);
    }

    /**
     * 延时签收
     *
     * 将自动签收时间往后延长 7 天，超时未签收由自动签收命令处理。
     *
     * @param  Order  $order  订单
     * @param  Authenticatable  $user  操作人
     *
     * @throws \Throwable 订单状态不允许或超出可延时期限
     */
    public function delaySign(Order $order, Authenticatable $user): void
    {
        OrderActions\DelaySign::dispatch($order, $user);
    }

    /**
     * 门店自提订单核销
     *
     * 仅门店自提（pickup）履约方式且处于待自提（PickupPending）状态的订单可核销。
     *
     * @param  Order  $order  订单
     * @param  Authenticatable  $user  核销操作人（商家）
     * @param  string  $pickupCode  核销码，必须是非空字符串
     *
     * @throws \RuntimeException|\Throwable 订单非自提履约方式、核销码为空或不匹配、状态不允许
     */
    public function verify(Order $order, Authenticatable $user, string $pickupCode): void
    {
        OrderActions\Verify::dispatch($order, $user, $pickupCode);
    }

    /**
     * 完成订单
     *
     * @param  Order  $order  订单
     * @param  Authenticatable  $user  操作人
     *
     * @throws \Throwable 事务异常
     */
    public function complete(Order $order, Authenticatable $user): void
    {
        OrderActions\Complete::dispatch($order, $user);
    }

    /**
     * 订单开始备货
     *
     * @param  Order  $order  订单
     * @param  Authenticatable  $user  操作人
     *
     * @throws \Throwable
     */
    public function preparing(Order $order, Authenticatable $user): void
    {
        OrderActions\Preparing::dispatch($order, $user);
    }

    /**
     * 修改订单收货地址
     *
     * @param  Order  $order  订单
     * @param  array  $data  新地址数据
     * @param  Authenticatable  $user  操作人
     *
     * @throws \RuntimeException|\Throwable 订单状态不可修改地址
     */
    public function modifyAddress(Order $order, array $data, Authenticatable $user): void
    {
        OrderActions\ModifyAddress::dispatch($order, $data, $user);
    }

    /**
     * 添加商家备注
     *
     * @param  Order  $order  订单
     * @param  string  $remark  备注内容
     * @param  Authenticatable  $user  操作人
     *
     * @throws \Throwable
     */
    public function addSellerRemark(Order $order, string $remark, Authenticatable $user): void
    {
        OrderActions\AddSellerRemark::dispatch($order, $remark, $user);
    }

    /**
     * 修改订单运费
     *
     * @param  Order  $order  订单
     * @param  float  $freight  新运费
     * @param  Authenticatable  $user  操作人
     *
     * @throws \Throwable 订单状态不允许或运费为负
     */
    public function updateFreight(Order $order, float $freight, Authenticatable $user): void
    {
        OrderActions\UpdateFreight::dispatch($order, $user, $freight);
    }
}
