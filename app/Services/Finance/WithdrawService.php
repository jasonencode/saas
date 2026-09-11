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
     * 账户余额校验、冻结与扣减都在事务内并在账户行上加锁，
     * 避免并发提现时各自读到「余额充足」而重复冻结。
     *
     * @param  int  $userId  用户 ID
     * @param  float  $amount  提现金额
     * @param  string  $gateway  提现方式
     * @param  array  $accountInfo  收款账户信息
     * @param  string|null  $remark  备注
     * @param  float  $fee  手续费
     *
     * @throws Exception|\Throwable
     *
     * @return WithdrawOrder 创建的提现订单
     */
    public function create(
        int $userId,
        float $amount,
        string $gateway,
        array $accountInfo,
        ?string $remark = null,
        float $fee = 0,
        ?string $ip = null,
        ?string $userAgent = null,
    ): WithdrawOrder {
        if ($amount <= 0) {
            throw new InvalidArgumentException('提现金额必须大于 0');
        }

        $actualAmount = bcsub($amount, $fee, 2);

        if (bccomp($actualAmount, '0.01', 2) < 0) {
            throw new InvalidArgumentException('实际到账金额不能小于 0.01');
        }

        return DB::transaction(function () use ($userId, $amount, $gateway, $accountInfo, $remark, $fee, $actualAmount, $ip, $userAgent) {
            $account = UserAccount::whereKey($userId)
                ->lockForUpdate()
                ->first();

            if (!$account) {
                throw new InvalidArgumentException('用户账户不存在');
            }

            if (!$account->payment_password) {
                throw new InvalidArgumentException('请先设置支付密码');
            }

            if (bccomp((string) $account->balance, (string) $amount, 2) < 0) {
                throw new InvalidArgumentException('余额不足');
            }

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
                'amount' => $amount,
                'fee' => $fee,
                'actual_amount' => $actualAmount,
                'gateway' => $gateway,
                'account_info' => $accountInfo,
                'remark' => $remark,
                'ip' => $ip,
                'user_agent' => $userAgent,
            ]);
        });
    }

    /**
     * 审核提现订单
     *
     * 状态校验在事务内基于行锁读出的最新状态进行，重复审核 /
     * 与取消并发时只有一方能通过校验，不会重复解冻。
     *
     * @param  WithdrawOrder  $order  提现订单
     * @param  bool  $approved  是否通过
     * @param  int  $reviewerId  审核人 ID
     * @param  string|null  $rejectReason  拒绝原因
     *
     * @throws Exception|\Throwable
     *
     * @return bool 是否成功
     */
    public function review(WithdrawOrder $order, bool $approved, int $reviewerId, ?string $rejectReason = null): bool
    {
        return DB::transaction(function () use ($order, $approved, $reviewerId, $rejectReason) {
            $locked = $this->lockOrder($order);

            if ($locked->status !== WithdrawOrderStatus::Pending) {
                throw new InvalidArgumentException('提现订单状态不正确');
            }

            $locked->update([
                'status' => $approved ? WithdrawOrderStatus::Approved : WithdrawOrderStatus::Rejected,
                'reviewer_id' => $reviewerId,
                'reviewed_at' => now(),
                'reject_reason' => $rejectReason,
            ]);

            if (!$approved) {
                // 审核拒绝，解冻金额
                $this->unfreeze($locked, '提现审核拒绝，解冻金额');
            }

            $order->refresh();

            return true;
        });
    }

    /**
     * 完成打款
     *
     * 状态校验同样基于行锁，重复确认打款时第二次会因状态已变而失败，
     * 不会重复扣减冻结金额。
     *
     * @param  WithdrawOrder  $order  提现订单
     * @param  string|null  $paymentNo  打款流水号
     *
     * @throws Exception|\Throwable
     *
     * @return bool 是否成功
     */
    public function complete(WithdrawOrder $order, ?string $paymentNo = null): bool
    {
        return DB::transaction(function () use ($order, $paymentNo) {
            $locked = $this->lockOrder($order);

            if ($locked->status !== WithdrawOrderStatus::Approved) {
                throw new InvalidArgumentException('提现订单状态不正确');
            }

            $locked->update([
                'status' => WithdrawOrderStatus::Completed,
                'payment_no' => $paymentNo,
                'paid_at' => now(),
            ]);

            // 扣减冻结金额
            $account = $this->lockAccount($locked);

            if ($account) {
                $account->decrement('frozen_balance', $locked->amount);
                $account->refresh();

                $account->logs()->create([
                    'type' => UserAccountLogType::Unfreeze,
                    'asset' => AccountAssetType::Balance,
                    'amount' => $locked->amount,
                    'before' => $account->balance + $locked->amount,
                    'after' => $account->balance,
                    'remark' => '提现完成，扣减冻结金额',
                ]);
            }

            $order->refresh();

            return true;
        });
    }

    /**
     * 取消提现订单
     *
     * @param  WithdrawOrder  $order  提现订单
     *
     * @throws Exception|\Throwable
     *
     * @return bool 是否成功
     */
    public function cancel(WithdrawOrder $order): bool
    {
        return DB::transaction(function () use ($order) {
            $locked = $this->lockOrder($order);

            if ($locked->status !== WithdrawOrderStatus::Pending) {
                throw new InvalidArgumentException('提现订单状态不可取消');
            }

            $locked->update(['status' => WithdrawOrderStatus::Cancelled]);

            // 解冻金额
            $this->unfreeze($locked, '提现取消，解冻金额');

            $order->refresh();

            return true;
        });
    }

    /**
     * 加行锁读取提现订单
     *
     * 调用方传入的实例可能是加锁前读到的旧快照，状态判断一律以锁内实例为准。
     *
     * @param  WithdrawOrder  $order  提现订单
     *
     * @throws InvalidArgumentException 订单不存在
     *
     * @return WithdrawOrder 加锁后的订单
     */
    private function lockOrder(WithdrawOrder $order): WithdrawOrder
    {
        $locked = WithdrawOrder::whereKey($order->getKey())
            ->lockForUpdate()
            ->first();

        if (!$locked) {
            throw new InvalidArgumentException('提现订单不存在');
        }

        return $locked;
    }

    /**
     * 加行锁读取提现订单所属账户
     *
     * @param  WithdrawOrder  $order  提现订单
     *
     * @return UserAccount|null 账户
     */
    private function lockAccount(WithdrawOrder $order): ?UserAccount
    {
        return UserAccount::whereKey($order->user_id)
            ->lockForUpdate()
            ->first();
    }

    /**
     * 解冻提现金额：冻结额退回可用余额
     *
     * @param  WithdrawOrder  $order  提现订单
     * @param  string  $remark  日志备注
     */
    private function unfreeze(WithdrawOrder $order, string $remark): void
    {
        $account = $this->lockAccount($order);

        if (!$account) {
            return;
        }

        $account->decrement('frozen_balance', $order->amount);
        $account->increment('balance', $order->amount);
        $account->refresh();

        $account->logs()->create([
            'type' => UserAccountLogType::Unfreeze,
            'asset' => AccountAssetType::Balance,
            'amount' => $order->amount,
            'before' => $account->balance - $order->amount,
            'after' => $account->balance,
            'remark' => $remark,
        ]);
    }
}
