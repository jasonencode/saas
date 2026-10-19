<?php

namespace App\Services\Finance;

use App\Contracts\ServiceInterface;
use App\Enums\Finance\AccountAssetType;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Mall\OrderLogAction;
use App\Enums\User\UserAccountLogType;
use App\Exceptions\Mall\InsufficientStockException;
use App\Exceptions\Mall\OrderNotPayableException;
use App\Models\Finance\PaymentOrder;
use App\Models\Finance\RechargeOrder;
use App\Models\Finance\UserAccount;
use App\Models\Mall\Order;
use App\Services\Mall\OrderService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
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
     * @throws Throwable 支付密码未设置 / 密码错误 / 余额不足
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

            // 锁内重新计算应付金额，避免支付单金额与订单应付金额不一致
            $amount = $this->payableAmount($payment);

            $accountService = service(UserAccountService::class);

            if (!$accountService->verifyPaymentPassword($account, $password)) {
                throw new InvalidArgumentException('支付密码错误');
            }

            $accountService->modifyAsset(
                account: $account,
                asset: AccountAssetType::Balance,
                amount: -$amount,
                remark: "支付单号# $payment->no",
                source: $payment,
                type: UserAccountLogType::Consume,
            );

            // 支付单金额与实扣金额对齐，保证记录一致性
            $payment->update(['amount' => $amount]);

            $this->markPaidWithBusiness($payment, $user);
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
     * 调用方需自行包事务，保证支付单状态与业务推进同成败。
     *
     * @param  PaymentOrder  $payment  支付单
     * @param  Authenticatable|null  $user  支付人（为空时取业务单据所属用户）
     * @param  string|null  $paymentNo  第三方支付流水号
     *
     * @throws RuntimeException 关联业务缺少所属用户
     * @throws Throwable 业务推进异常
     */
    public function markPaidWithBusiness(PaymentOrder $payment, ?Authenticatable $user = null, ?string $paymentNo = null): void
    {
        // 行锁 + 重读：微信重复投递/并发回调下只允许一个请求推进业务
        $payment = PaymentOrder::whereKey($payment->getKey())->lockForUpdate()->firstOrFail();

        if ($payment->status === PaymentStatus::Paid) {
            return;
        }

        $payment->update([
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);

        $paymentable = $payment->paymentable;

        if ($paymentable instanceof Order) {
            $operator = $user ?? $paymentable->user ?? throw new RuntimeException('订单缺少所属用户，无法推进支付');
            $orderService = service(OrderService::class);

            try {
                $orderService->pay($paymentable, $operator);
            } catch (OrderNotPayableException $e) {
                // 渠道已收款但订单已取消/已流转：不再返回 FAIL 让渠道无限重试，转为原路退款
                $this->refundReceivedPayment($payment, $paymentable, $operator, $e->getMessage());
            } catch (InsufficientStockException) {
                // 付款扣库存失败：关闭订单并原路退款，避免钱货两空
                $orderService->cancel($paymentable, $operator);
                $this->refundReceivedPayment($payment, $paymentable, $operator, '商品库存不足');
            }
        }

        if ($paymentable instanceof RechargeOrder) {
            $rechargeService = service(RechargeService::class);
            $rechargeService->markPaid($paymentable, $paymentNo);
            $rechargeService->complete($paymentable);
        }
    }

    /**
     * 为「已收款但无法履约」的订单发起原路退款
     *
     * 支付退款单保持待审核，由财务确认后原路退回；订单侧记录操作日志便于对账。
     *
     * @param  PaymentOrder  $payment  已支付的支付单
     * @param  Order  $order  无法履约的订单
     * @param  Authenticatable  $operator  操作人
     * @param  string  $reason  退款原因
     *
     * @throws Throwable
     */
    private function refundReceivedPayment(PaymentOrder $payment, Order $order, Authenticatable $operator, string $reason): void
    {
        $refund = service(PaymentRefundService::class)->create(
            payment: $payment,
            amount: (float) $payment->amount,
            reason: sprintf('%s，自动原路退款', $reason),
            creator: $operator instanceof Model ? $operator : null,
            source: $order,
        );

        service(OrderService::class)->log(
            order: $order,
            action: OrderLogAction::RefundCreated,
            user: $operator,
            remark: '支付成功后订单不可履约，已自动发起原路退款',
            context: [
                'payment_no' => $payment->no,
                'payment_refund_no' => $refund->no,
                'amount' => $refund->amount,
                'reason' => $reason,
            ],
        );
    }

    /**
     * 计算应付金额
     *
     * 关联商城订单时取订单应付总额，否则回退支付单记录金额。
     *
     * @param  PaymentOrder  $payment  支付单
     *
     * @return float 应付金额
     */
    private function payableAmount(PaymentOrder $payment): float
    {
        if ($payment->paymentable) {
            return PaymentableResolver::amountOf($payment->paymentable)
                ?? (float) $payment->amount;
        }

        return (float) $payment->amount;
    }
}
