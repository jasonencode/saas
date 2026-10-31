<?php

namespace App\Services\Mall;

use App\Contracts\Authenticatable;
use App\Contracts\ServiceInterface;
use App\Enums\Mall\RefundStatus;
use App\Enums\Mall\RefundType;
use App\Models\Mall\Order;
use App\Models\Mall\OrderItem;
use App\Models\Mall\Refund;
use App\Services\Mall\DTOs\RefundData;
use App\Services\System\EventLogService;
use Throwable;

/**
 * 退款服务：退款申请、审核、退货物流、支付退款与退款完成
 *
 * 申请所需数据（订单、商品明细、原因、备注、运费）全部由调用方显式传入，
 * 服务内部不读取 Auth 或请求上下文；操作人（用户 / 管理员 / 系统 / 队列账号）
 * 也作为 Authenticatable 显式传入。
 *
 * 门面方法只做参数转交，具体流程见 RefundActions\*：
 * 申请 → 审核通过/驳回 → 取消 → 退货发货 → 退货签收 → 发起退款（支付退款单）
 * → 退款完成 → 重试/失败。
 *
 * 操作日志统一由 System\EventLogService 写入日志中心（event_logs），在动作的 log() 阶段完成。
 */
class RefundService implements ServiceInterface
{
    /**
     * 创建退款申请
     *
     * @param  Order  $order  订单
     * @param  Authenticatable  $user  用户
     * @param  RefundData  $data  退款数据（已校验）
     *
     * @throws Throwable 订单不可退款或数据验证失败
     *
     * @return Refund 创建的退款单
     */
    public function createRefund(Order $order, Authenticatable $user, RefundData $data): Refund
    {
        return RefundActions\Apply::dispatch($order, $user, $data);
    }

    /**
     * 运营 / 租户代用户发起退款
     *
     * 与用户自助申请走同一套数量、金额与并发校验，区别是允许商家对已完成等
     * 售后状态代用户发起，并由操作人写入日志。
     *
     * @param  Order  $order  订单
     * @param  Authenticatable  $operator  操作人（后台管理员 / 租户管理员）
     * @param  RefundData  $data  退款数据（已校验）
     *
     * @throws Throwable 订单不可退款或数据验证失败
     *
     * @return Refund 创建的退款单
     */
    public function createRefundForOperator(Order $order, Authenticatable $operator, RefundData $data): Refund
    {
        return RefundActions\Apply::dispatch($order, $operator, $data, operatorInitiated: true);
    }

    /**
     * 验证订单是否可退款
     *
     * @param  Order  $order  订单
     *
     * @throws \InvalidArgumentException 订单状态不支持退款
     * @throws \RuntimeException 已有退款申请正在处理中
     */
    public function validateOrderForRefund(Order $order): void
    {
        RefundActions\Apply::assertOrderRefundable($order);
    }

    /**
     * 获取订单允许的退款类型
     *
     * @param  Order  $order  订单
     *
     * @return RefundType[]
     */
    public function getAllowedRefundTypes(Order $order): array
    {
        return RefundActions\Apply::allowedTypes($order);
    }

    /**
     * 判断订单是否可退款
     *
     * 订单状态允许且仍有可退商品数量时返回 true。
     *
     * @param  Order  $order  订单
     */
    public function isOrderRefundable(Order $order): bool
    {
        try {
            $this->validateOrderForRefund($order);
        } catch (Throwable) {
            return false;
        }

        return $this->hasRefundableItems($order);
    }

    /**
     * 订单是否还有可退商品数量
     *
     * 所有订单项的可退数量都为 0 时视为不可再申请退款。
     *
     * @param  Order  $order  订单
     */
    public function hasRefundableItems(Order $order): bool
    {
        $order->loadMissing('items.refundItems.refund');

        return $order->items->contains(fn (OrderItem $item): bool => $item->getMaxRefundCounts() > 0);
    }

    /**
     * 运营 / 租户是否可以代用户发起退款
     *
     * 允许已完成等售后状态，但仍要求订单已付款、无处理中退款且还有可退商品。
     *
     * @param  Order  $order  订单
     */
    public function isOrderRefundableByOperator(Order $order): bool
    {
        try {
            RefundActions\Apply::assertOperatorOrderRefundable($order);
        } catch (Throwable) {
            return false;
        }

        return $this->hasRefundableItems($order);
    }

    /**
     * 获取运营 / 租户代用户退款允许的类型
     *
     * @param  Order  $order  订单
     *
     * @return RefundType[]
     */
    public function getOperatorRefundTypes(Order $order): array
    {
        return RefundActions\Apply::operatorAllowedTypes($order);
    }

    /**
     * 取消退款申请
     *
     * @param  Refund  $refund  退款单
     * @param  Authenticatable  $user  用户
     *
     * @throws Throwable 退款单状态不允许取消
     */
    public function cancelRefund(Refund $refund, Authenticatable $user): void
    {
        RefundActions\Cancel::dispatch($refund, $user);
    }

    /**
     * 审核通过
     *
     * 根据退款类型决定下一步流转：
     * - 仅退款 → 直接进入退款处理
     * - 退货退款 → 进入等待退货流程
     *
     * @param  Refund  $refund  退款单
     * @param  Authenticatable  $user  审核人
     * @param  string|null  $remark  审核备注
     *
     * @throws Throwable 退款单状态不允许审核
     */
    public function approveRefund(Refund $refund, Authenticatable $user, ?string $remark = null): void
    {
        RefundActions\Approve::dispatch($refund, $user, $remark);
    }

    /**
     * 审核驳回
     *
     * @param  Refund  $refund  退款单
     * @param  Authenticatable  $user  审核人
     * @param  string  $remark  驳回原因
     *
     * @throws Throwable 退款单状态不允许审核
     */
    public function rejectRefund(Refund $refund, Authenticatable $user, string $remark): void
    {
        RefundActions\Reject::dispatch($refund, $user, $remark);
    }

    /**
     * 用户提交退货物流
     *
     * @param  Refund  $refund  退款单
     * @param  Authenticatable  $user  用户
     * @param  array  $expressData  物流数据（express_id, express_no）
     *
     * @throws Throwable 退款单状态不允许提交物流
     */
    public function shipReturn(Refund $refund, Authenticatable $user, array $expressData): void
    {
        RefundActions\ShipReturn::dispatch($refund, $user, $expressData);
    }

    /**
     * 商户确认签收退货
     *
     * @param  Refund  $refund  退款单
     * @param  Authenticatable  $user  操作人
     * @param  string|null  $remark  备注
     *
     * @throws Throwable 退款单状态不允许确认签收
     */
    public function confirmReceive(Refund $refund, Authenticatable $user, ?string $remark = null): void
    {
        RefundActions\ConfirmReceive::dispatch($refund, $user, $remark);
    }

    /**
     * 发起退款（创建支付退款单）
     *
     * 退款资金由支付侧执行（微信原路退回 / 余额退回用户账户），本方法只登记待审核的支付退款单；
     * 支付退款成功后由监听器回调 confirmRefund() 完成售后单。
     *
     * @param  Refund  $refund  退款单
     * @param  Authenticatable  $user  操作人
     *
     * @throws Throwable 退款单状态不允许或订单没有可退款的支付单
     */
    public function processRefund(Refund $refund, Authenticatable $user): void
    {
        RefundActions\Process::dispatch($refund, $user);
    }

    /**
     * 确认退款（执行退款完成）
     *
     * @param  Refund  $refund  退款单
     * @param  Authenticatable  $user  操作人
     * @param  string|null  $remark  备注
     *
     * @throws Throwable 退款单状态不允许确认退款
     */
    public function confirmRefund(Refund $refund, Authenticatable $user, ?string $remark = null): void
    {
        RefundActions\Complete::dispatch($refund, $user, $remark);
    }

    /**
     * 支付退款被驳回/取消：售后单同步失败
     *
     * @param  Refund  $refund  退款单
     * @param  string  $reason  失败原因
     */
    public function failFromPaymentRefund(Refund $refund, string $reason): void
    {
        if (in_array($refund->status, RefundStatus::terminalCases(), true)) {
            return;
        }

        if ($refund->status !== RefundStatus::Processing) {
            return;
        }

        RefundActions\Fail::dispatch($refund, $refund->user, "资金退款失败：{$reason}");
    }

    /**
     * 支付退款通道失败（可重试）：仅记录日志，售后单保持处理中
     *
     * @param  Refund  $refund  退款单
     * @param  string  $reason  失败原因
     */
    public function logPaymentFailure(Refund $refund, string $reason): void
    {
        service(EventLogService::class)->write(
            'refund.payment_failed',
            '支付退款失败',
            $refund->user,
            $refund,
            "支付退款失败，待重试：{$reason}",
            [
                'payment_failed' => true,
                'fail_reason' => $reason,
            ]
        );
    }

    /**
     * 重试退款（处理退款失败重试）
     *
     * 仅支持 Failed 状态的退款单重试，重试后状态回到 Processing。
     *
     * @param  Refund  $refund  退款单
     * @param  Authenticatable  $user  操作人
     * @param  string|null  $remark  备注
     *
     * @throws Throwable 退款单状态不允许重试
     */
    public function retryRefund(Refund $refund, Authenticatable $user, ?string $remark = null): void
    {
        RefundActions\Retry::dispatch($refund, $user, $remark);
    }

    /**
     * 标记退款失败
     *
     * 将 Processing 状态的退款单标记为失败。
     *
     * @param  Refund  $refund  退款单
     * @param  Authenticatable  $user  操作人
     * @param  string  $remark  失败原因
     *
     * @throws Throwable 退款单状态不允许标记失败
     */
    public function markRefundFailed(Refund $refund, Authenticatable $user, string $remark): void
    {
        RefundActions\Fail::dispatch($refund, $user, $remark);
    }

    /**
     * 重新发起退款（真正重试支付退款单）
     *
     * 与 retryRefund() 的区别：retryRefund() 只把失败的售后单重置为处理中，
     * 本方法会重试原支付通道；支付退款单已成功时补完成售后单。
     *
     * @param  Refund  $refund  退款单
     * @param  Authenticatable  $user  操作人
     *
     * @throws Throwable 退款单状态不允许或支付通道仍失败
     */
    public function reRefund(Refund $refund, Authenticatable $user): void
    {
        RefundActions\ReRefund::dispatch($refund, $user);
    }

    /**
     * 重置退款申请（已拒绝/已取消/失败 → 待审核）
     *
     * 重置后需重新审核；只重置退款单，不改变原订单状态。
     *
     * @param  Refund  $refund  退款单
     * @param  Authenticatable  $user  操作人
     * @param  string|null  $remark  备注
     *
     * @throws Throwable 退款单状态不允许或商品可退数量不足
     */
    public function reApplyRefund(Refund $refund, Authenticatable $user, ?string $remark = null): void
    {
        RefundActions\ReApply::dispatch($refund, $user, $remark);
    }

    /**
     * 修改退款金额（打款前调整商品金额与运费）
     *
     * @param  Refund  $refund  退款单
     * @param  Authenticatable  $user  操作人
     * @param  float  $goodsAmount  商品退款金额
     * @param  float  $freightAmount  退款运费
     *
     * @throws Throwable 退款单状态不允许或金额超出可退范围
     */
    public function updateRefundAmount(
        Refund $refund,
        Authenticatable $user,
        float $goodsAmount,
        float $freightAmount = 0.0,
    ): void {
        RefundActions\UpdateAmount::dispatch($refund, $user, $goodsAmount, $freightAmount);
    }

    /**
     * 删除退款单（已拒绝/已取消，软删）
     *
     * @param  Refund  $refund  退款单
     * @param  Authenticatable  $user  操作人
     *
     * @throws Throwable 退款单状态不允许删除
     */
    public function destroyRefund(Refund $refund, Authenticatable $user): void
    {
        RefundActions\Destroy::dispatch($refund, $user);
    }
}
