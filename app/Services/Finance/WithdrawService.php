<?php

namespace App\Services\Finance;

use App\Contracts\ServiceInterface;
use App\Enums\Finance\AccountAssetType;
use App\Enums\Finance\WithdrawOrderStatus;
use App\Enums\User\UserAccountLogType;
use App\Models\Finance\UserAccount;
use App\Models\Finance\WithdrawOrder;
use Exception;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class WithdrawService implements ServiceInterface
{
    /**
     * 创建提现订单
     *
     * @param  int  $userId  用户 ID
     * @param  int|null  $tenantId  租户 ID
     * @param  float  $amount  提现金额
     * @param  string  $gateway  提现方式
     * @param  array  $accountInfo  收款账户信息
     * @param  string|null  $remark  备注
     * @param  float  $fee  手续费
     *
     * @throws Exception
     *
     * @return WithdrawOrder 创建的提现订单
     */
    public function create(
        int $userId,
        ?int $tenantId,
        float $amount,
        string $gateway,
        array $accountInfo,
        ?string $remark = null,
        float $fee = 0,
    ): WithdrawOrder {
        if ($amount <= 0) {
            throw new InvalidArgumentException('提现金额必须大于 0');
        }

        $account = UserAccount::find($userId);

        if (!$account) {
            throw new InvalidArgumentException('用户账户不存在');
        }

        if (!$account->payment_password) {
            throw new InvalidArgumentException('请先设置支付密码');
        }

        if (bccomp((string) $account->balance, (string) $amount, 2) < 0) {
            throw new InvalidArgumentException('余额不足');
        }

        $actualAmount = bcsub($amount, $fee, 2);

        if (bccomp($actualAmount, '0.01', 2) < 0) {
            throw new InvalidArgumentException('实际到账金额不能小于 0.01');
        }

        return DB::transaction(static function () use ($userId, $tenantId, $amount, $gateway, $accountInfo, $remark, $fee, $actualAmount, $account) {
            // 冻结提现金额
            $account->decrement('balance', $amount);
            $account->increment('frozen_balance', $amount);
            $account->refresh();

            // 记录账户日志
            $account->logs()->create([
                'type' => UserAccountLogType::Freeze,
                'asset' => AccountAssetType::Balance,
                'amount' => -$amount,
                'before' => $account->balance + $amount,
                'after' => $account->balance,
                'remark' => '提现冻结',
            ]);

            return WithdrawOrder::create([
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'amount' => $amount,
                'fee' => $fee,
                'actual_amount' => $actualAmount,
                'gateway' => $gateway,
                'account_info' => $accountInfo,
                'remark' => $remark,
            ]);
        });
    }

    /**
     * 审核提现订单
     *
     * @param  WithdrawOrder  $order  提现订单
     * @param  bool  $approved  是否通过
     * @param  int  $reviewerId  审核人 ID
     * @param  string|null  $rejectReason  拒绝原因
     *
     * @throws Exception
     *
     * @return bool 是否成功
     */
    public function review(WithdrawOrder $order, bool $approved, int $reviewerId, ?string $rejectReason = null): bool
    {
        if ($order->status !== WithdrawOrderStatus::Pending) {
            throw new InvalidArgumentException('提现订单状态不正确');
        }

        return DB::transaction(static function () use ($order, $approved, $reviewerId, $rejectReason) {
            $order->update([
                'status' => $approved ? WithdrawOrderStatus::Approved : WithdrawOrderStatus::Rejected,
                'reviewer_id' => $reviewerId,
                'reviewed_at' => now(),
                'reject_reason' => $rejectReason,
            ]);

            if (!$approved) {
                // 审核拒绝，解冻金额
                $account = UserAccount::find($order->user_id);
                if ($account) {
                    $account->decrement('frozen_balance', $order->amount);
                    $account->increment('balance', $order->amount);
                    $account->refresh();

                    $account->logs()->create([
                        'type' => UserAccountLogType::Unfreeze,
                        'asset' => AccountAssetType::Balance,
                        'amount' => $order->amount,
                        'before' => $account->balance - $order->amount,
                        'after' => $account->balance,
                        'remark' => '提现审核拒绝，解冻金额',
                    ]);
                }
            }

            return true;
        });
    }

    /**
     * 完成打款
     *
     * @param  WithdrawOrder  $order  提现订单
     * @param  string|null  $paymentNo  打款流水号
     *
     * @throws Exception
     *
     * @return bool 是否成功
     */
    public function complete(WithdrawOrder $order, ?string $paymentNo = null): bool
    {
        if ($order->status !== WithdrawOrderStatus::Approved) {
            throw new InvalidArgumentException('提现订单状态不正确');
        }

        return DB::transaction(static function () use ($order, $paymentNo) {
            $order->update([
                'status' => WithdrawOrderStatus::Completed,
                'payment_no' => $paymentNo,
                'paid_at' => now(),
            ]);

            // 扣减冻结金额
            $account = UserAccount::find($order->user_id);
            if ($account) {
                $account->decrement('frozen_balance', $order->amount);
                $account->refresh();

                $account->logs()->create([
                    'type' => UserAccountLogType::Unfreeze,
                    'asset' => AccountAssetType::Balance,
                    'amount' => $order->amount,
                    'before' => $account->balance + $order->amount,
                    'after' => $account->balance,
                    'remark' => '提现完成，扣减冻结金额',
                ]);
            }

            return true;
        });
    }

    /**
     * 取消提现订单
     *
     * @param  WithdrawOrder  $order  提现订单
     *
     * @throws Exception
     *
     * @return bool 是否成功
     */
    public function cancel(WithdrawOrder $order): bool
    {
        if (!in_array($order->status, [WithdrawOrderStatus::Pending])) {
            throw new InvalidArgumentException('提现订单状态不可取消');
        }

        return DB::transaction(static function () use ($order) {
            $order->update(['status' => WithdrawOrderStatus::Cancelled]);

            // 解冻金额
            $account = UserAccount::find($order->user_id);
            if ($account) {
                $account->decrement('frozen_balance', $order->amount);
                $account->increment('balance', $order->amount);
                $account->refresh();

                $account->logs()->create([
                    'type' => UserAccountLogType::Unfreeze,
                    'asset' => AccountAssetType::Balance,
                    'amount' => $order->amount,
                    'before' => $account->balance - $order->amount,
                    'after' => $account->balance,
                    'remark' => '提现取消，解冻金额',
                ]);
            }

            return true;
        });
    }
}
