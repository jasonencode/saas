<?php

namespace App\Services\Finance;

use App\Contracts\ServiceInterface;
use App\Enums\Finance\AccountAssetType;
use App\Enums\Finance\PaymentStatus;
use App\Enums\User\UserAccountLogType;
use App\Models\Finance\PaymentOrder;
use App\Models\Finance\UserAccount;
use App\Models\Mall\Order;
use App\Services\Mall\OrderService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

class PaymentService implements ServiceInterface
{
    /**
     * 余额支付
     *
     * 校验支付密码并从用户余额扣除应付金额，标记支付单为已支付；
     * 若关联商城订单，则同步推进订单状态（与后台 OrderPaymentAction 口径一致）。
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

        // 应付金额：关联订单时以订单应付总额（含运费）为准，避免客户端伪造支付单金额低价买单
        $amount = $this->payableAmount($payment);

        DB::transaction(static function () use ($account, $payment, $password, $amount, $user) {
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
            $payment->update([
                'amount' => $amount,
                'status' => PaymentStatus::Paid,
                'paid_at' => now(),
            ]);

            if ($payment->paymentable instanceof Order) {
                service(OrderService::class)->pay($payment->paymentable, $user);
            }
        });
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
