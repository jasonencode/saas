<?php

namespace App\Services\Finance;

use App\Contracts\ServiceInterface;
use App\Enums\Finance\AccountAssetType;
use App\Enums\Finance\PaymentGateway;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Finance\RechargeOrderStatus;
use App\Enums\Mall\OrderStatus;
use App\Enums\User\UserAccountLogType;
use App\Exceptions\Mall\InsufficientStockException;
use App\Exceptions\Mall\OrderNotPayableException;
use App\Models\Finance\PaymentOrder;
use App\Models\Finance\RechargeOrder;
use App\Models\Finance\UserAccount;
use App\Models\Mall\Order;
use App\Notifications\Finance\PaymentRefundInitiatedNotification;
use App\Services\Mall\OrderService;
use App\Services\System\EventLogService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

class PaymentService implements ServiceInterface
{
    /**
     * 余额支付
     *
     * 校验支付密码并从用户余额扣除应付金额，标记支付单为已支付并推进关联业务；
     * 应付金额以关联业务模型为准（含运费），避免客户端伪造支付单金额低价买单。
     *
     * @param  PaymentOrder  $payment  支付单
     * @param  string  $password  支付密码
     * @param  Authenticatable  $user  操作人
     *
     * @throws Throwable 支付密码未设置 / 密码错误 / 余额不足 / 业务不可支付
     */
    public function payByBalance(PaymentOrder $payment, string $password, Authenticatable $user): void
    {
        $account = UserAccount::find($payment->user_id);

        if (!$account) {
            throw new InvalidArgumentException('用户账户不存在');
        }

        // 未设置支付密码时提前提示，避免进入事务后才由密码校验抛出
        if (!$account->payment_password) {
            throw new InvalidArgumentException('使用余额支付前，请先设置支付密码');
        }

        if ($payment->gateway !== PaymentGateway::Balance) {
            throw new InvalidArgumentException('该支付单不是余额支付');
        }

        // 充值订单不能用余额支付
        if ($payment->paymentable instanceof RechargeOrder) {
            throw new InvalidArgumentException('充值订单不支持余额支付，请选择其他支付方式');
        }

        DB::transaction(function () use ($account, $payment, $password, $user) {
            // 锁内重读支付单：重复提交/并发请求时只允许扣一次余额
            $payment = PaymentOrder::whereKey($payment->getKey())->lockForUpdate()->firstOrFail();

            if ($payment->status === PaymentStatus::Paid) {
                return;
            }

            if ($payment->status !== PaymentStatus::Pending) {
                throw new InvalidArgumentException('支付单当前状态不可支付');
            }

            // 扣款前先确认业务仍可支付，避免「先扣余额、再自动退款」
            $amount = $this->payableAmount($payment);

            $accountService = service(UserAccountService::class);

            if (!$accountService->verifyPaymentPassword($account, $password)) {
                throw new InvalidArgumentException('支付密码错误');
            }

            $accountService->modifyAsset(
                account: $account,
                asset: AccountAssetType::Balance,
                amount: bcmul($amount, '-1', 2),
                remark: "支付单号# $payment->no",
                source: $payment,
                type: UserAccountLogType::Consume,
                idempotencyKey: $this->paymentLogKey($payment),
            );

            // 支付单金额与实扣金额对齐，保证记录一致性
            $payment->update(['amount' => $amount]);

            $this->markPaidWithBusiness($payment, $user, paidAmount: $amount);
        });
    }

    /**
     * 标记支付单已支付并推进关联业务
     *
     * 余额支付与第三方支付回调的**统一出口**：先落支付单的已支付状态，
     * 再按 paymentable 类型推进业务单据：
     * - 商城订单 → OrderService::pay()（按履约方式推进状态、生成核销码、派发事件）
     * - 充值订单 → 标记已支付并完成到账（余额 / 积分入账）
     *
     * 幂等：支付单已是已支付状态时直接返回，微信重复投递回调不会重复推进业务。
     * 不可履约、业务已删除、重复支付等场景会创建自动退款单，避免渠道收款后无后续处理。
     *
     * @param  PaymentOrder  $payment  支付单
     * @param  Authenticatable|null  $user  支付人（为空时取业务单据所属用户）
     * @param  string|null  $paymentNo  第三方支付流水号
     * @param  string|null  $paidAmount  渠道实收金额（为空时取支付单金额）
     *
     * @throws Throwable 业务推进异常
     */
    public function markPaidWithBusiness(
        PaymentOrder $payment,
        ?Authenticatable $user = null,
        ?string $paymentNo = null,
        ?string $paidAmount = null,
    ): void {
        // 行锁 + 重读：微信重复投递/并发回调下只允许一个请求推进业务
        $payment = PaymentOrder::whereKey($payment->getKey())->lockForUpdate()->firstOrFail();

        if ($payment->status === PaymentStatus::Paid || $payment->status === PaymentStatus::Refunded) {
            return;
        }

        $operator = $user;

        $payment->update([
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
            'transaction_id' => $paymentNo,
            'paid_amount' => $paidAmount ?? $payment->amount,
        ]);

        // 先记资金到账，再推进业务：业务推进失败（订单已取消 / 库存不足）时，
        // 这条记录仍能证明渠道已收款，便于对账与原路退款
        service(EventLogService::class)->write(
            'payment.paid',
            '支付成功',
            $payment->user,
            $payment,
            null,
            [
                'gateway' => $payment->gateway->value,
                'amount' => $payment->amount,
                'paid_amount' => $payment->paid_amount,
                'transaction_id' => $paymentNo,
                'paymentable_type' => $payment->paymentable_type,
                'paymentable_id' => $payment->paymentable_id,
            ]
        );

        $paymentable = $payment->paymentable;

        // 业务单据已删除：渠道已收款但无法履约，直接发起原路退款
        if ($payment->paymentable_type !== null && !$paymentable) {
            $this->createAutomaticRefund($payment, null, $operator, '支付关联业务已删除');

            return;
        }

        if ($paymentable instanceof Order) {
            $operator ??= $paymentable->user;

            if (!$operator) {
                $this->createAutomaticRefund($payment, $paymentable, null, '订单缺少所属用户，无法推进支付');

                return;
            }

            $orderService = service(OrderService::class);

            try {
                $orderService->pay($paymentable, $operator);
            } catch (OrderNotPayableException $e) {
                // 渠道已收款但订单已取消/已流转：不再返回 FAIL 让渠道无限重试，转为原路退款
                $this->createAutomaticRefund($payment, $paymentable, $operator, $e->getMessage());
            } catch (InsufficientStockException) {
                // 付款扣库存失败：关闭订单并原路退款，避免钱货两空
                $orderService->cancel($paymentable, $operator);
                $this->createAutomaticRefund($payment, $paymentable, $operator, '商品库存不足');
            }

            return;
        }

        if ($paymentable instanceof RechargeOrder) {
            $rechargeService = service(RechargeService::class);

            // 同一充值单已被其他支付单完成：当前支付单原路退回，避免重复入账或资金卡死
            if (!$rechargeService->markPaid($paymentable, $paymentNo)) {
                $this->createAutomaticRefund($payment, null, $operator, '充值订单已通过其他支付单完成');

                return;
            }

            $rechargeService->complete($paymentable);
        }
    }

    /**
     * 计算业务单据应付金额
     *
     * 关联商城订单/充值单时以业务模型为准，否则直接拒绝，避免裸支付单扣款。
     *
     * @param  Model  $paymentable  可支付业务单据
     *
     * @throws InvalidArgumentException 业务不可支付
     *
     * @return string 应付金额（两位小数字符串）
     */
    public function payableAmountFor(Model $paymentable): string
    {
        if ($paymentable instanceof Order) {
            if ($paymentable->status !== OrderStatus::Pending) {
                throw new OrderNotPayableException($paymentable, '订单当前状态不可支付');
            }

            if ($paymentable->expired_at?->isPast()) {
                throw new InvalidArgumentException('订单已过期');
            }

            return $paymentable->getTotalAmount();
        }

        if ($paymentable instanceof RechargeOrder) {
            if ($paymentable->status !== RechargeOrderStatus::Pending) {
                throw new InvalidArgumentException('充值订单当前状态不可支付');
            }

            if ($paymentable->expired_at?->isPast()) {
                throw new InvalidArgumentException('充值订单已过期');
            }

            return $paymentable->getTotalAmount();
        }

        throw new InvalidArgumentException('不支持的支付关联类型');
    }

    /**
     * 为「已收款但无法履约」的支付单发起原路退款
     *
     * 退款单创建后自动审核并执行（事务提交后），失败时保留待审核/失败状态供财务重试。
     *
     * @param  PaymentOrder  $payment  已支付的支付单
     * @param  Order|null  $order  无法履约的订单（业务已删除时为空）
     * @param  Authenticatable|null  $operator  操作人
     * @param  string  $reason  退款原因
     *
     * @throws Throwable 创建退款单异常
     */
    private function createAutomaticRefund(
        PaymentOrder $payment,
        ?Order $order,
        ?Authenticatable $operator,
        string $reason,
    ): void {
        $amount = (string) ($payment->paid_amount ?? $payment->amount);

        $refund = service(PaymentRefundService::class)->create(
            payment: $payment,
            amount: $amount,
            reason: sprintf('%s，自动原路退款', $reason),
            creator: $operator instanceof Model ? $operator : null,
            source: $order,
            ip: app()->runningInConsole() ? null : request()->ip(),
            userAgent: app()->runningInConsole() ? null : request()->userAgent(),
        );

        service(EventLogService::class)->write(
            'order.refund_created',
            '发起退款',
            $operator instanceof Model ? $operator : null,
            $order,
            '支付成功后无法履约，已自动发起原路退款',
            [
                'payment_no' => $payment->no,
                'payment_refund_no' => $refund->no,
                'amount' => $refund->amount,
                'reason' => $reason,
            ]
        );

        DB::afterCommit(static function () use ($payment, $refund): void {
            try {
                $payment->user?->notify(new PaymentRefundInitiatedNotification($refund));
            } catch (Throwable $e) {
                report($e);
            }

            try {
                $service = service(PaymentRefundService::class);
                $service->autoApprove($refund);
                $service->execute($refund);
            } catch (Throwable $e) {
                // 自动退款失败保留退款单，由财务在后台确认或重试
                report($e);
            }
        });
    }

    /**
     * 计算支付单应付金额
     *
     * @param  PaymentOrder  $payment  支付单
     *
     * @throws InvalidArgumentException 支付单未关联业务或业务不可支付
     *
     * @return string 应付金额（两位小数字符串）
     */
    private function payableAmount(PaymentOrder $payment): string
    {
        if ($payment->paymentable_type === null) {
            throw new InvalidArgumentException('支付单未关联业务单据，无法支付');
        }

        $paymentable = $payment->paymentable;

        if (!$paymentable) {
            throw new InvalidArgumentException('支付关联业务已删除，无法支付');
        }

        return $this->payableAmountFor($paymentable);
    }

    /**
     * 余额支付日志幂等键
     *
     * @param  PaymentOrder  $payment  支付单
     *
     * @return string 幂等键
     */
    private function paymentLogKey(PaymentOrder $payment): string
    {
        return sprintf('payment_order:%s:consume', $payment->getKey());
    }
}
