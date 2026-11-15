<?php

namespace App\Services\Finance;

use App\Contracts\ServiceInterface;
use App\Enums\Finance\AccountAssetType;
use App\Enums\Finance\PaymentGateway;
use App\Enums\Finance\RechargeOrderStatus;
use App\Enums\Finance\RechargeOrderType;
use App\Enums\User\UserAccountLogType;
use App\Models\Finance\RechargeOrder;
use App\Models\Finance\UserAccount;
use App\Models\Finance\UserAccountLog;
use App\Services\System\EventLogService;
use Exception;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RechargeService implements ServiceInterface
{
    /**
     * 创建充值订单
     *
     * @param  int  $userId  用户 ID
     * @param  int|null  $tenantId  租户 ID
     * @param  float  $amount  充值金额
     * @param  RechargeOrderType  $type  充值类型
     * @param  PaymentGateway  $gateway  支付网关
     * @param  float|null  $receivedAmount  到账金额（为空时等于充值金额）
     * @param  string|null  $remark  备注
     *
     * @throws Exception
     *
     * @return RechargeOrder 创建的充值订单
     */
    public function create(
        int $userId,
        ?int $tenantId,
        float $amount,
        RechargeOrderType $type,
        PaymentGateway $gateway,
        ?float $receivedAmount = null,
        ?string $remark = null,
    ): RechargeOrder {
        if ($amount <= 0) {
            throw new InvalidArgumentException('充值金额必须大于 0');
        }

        return RechargeOrder::create([
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'type' => $type,
            'gateway' => $gateway,
            'amount' => $amount,
            'received_amount' => $receivedAmount ?? $amount,
            'remark' => $remark,
            'expired_at' => now()->addMinutes(30),
        ]);
    }

    /**
     * 完成充值（余额充值）
     *
     * 支付成功后将金额增加到用户账户余额，并记录变动日志。
     *
     * @param  RechargeOrder  $order  充值订单
     *
     * @throws Exception
     *
     * @return bool 是否成功
     */
    public function complete(RechargeOrder $order): bool
    {
        return DB::transaction(function () use ($order) {
            // 行锁串行化：同一充值单只允许入账一次
            $locked = RechargeOrder::whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status === RechargeOrderStatus::Completed) {
                return true;
            }

            if ($locked->status !== RechargeOrderStatus::Paid) {
                throw new InvalidArgumentException('充值订单状态不正确');
            }

            $asset = $locked->type === RechargeOrderType::Balance
                ? AccountAssetType::Balance
                : AccountAssetType::Points;
            $idempotencyKey = sprintf('recharge_order:%s:recharge', $locked->getKey());

            // 幂等：同一充值单已有入账流水时不再重复加钱
            if (!UserAccountLog::query()->where('idempotency_key', $idempotencyKey)->exists()) {
                $account = UserAccount::firstOrCreate(
                    ['user_id' => $locked->user_id],
                    [
                        'balance' => 0,
                        'frozen_balance' => 0,
                        'points' => 0,
                        'frozen_points' => 0,
                    ]
                );

                service(UserAccountService::class)->modifyAsset(
                    account: $account,
                    asset: $asset,
                    amount: $locked->received_amount,
                    remark: "充值单号# {$locked->no}",
                    source: $locked,
                    type: UserAccountLogType::Recharge,
                    idempotencyKey: $idempotencyKey,
                );
            }

            $affected = RechargeOrder::whereKey($locked->getKey())
                ->where('status', RechargeOrderStatus::Paid)
                ->update([
                    'status' => RechargeOrderStatus::Completed,
                    'completed_at' => now(),
                ]);

            if ($affected === 0) {
                $order->refresh();

                return true;
            }

            $order->refresh();

            service(EventLogService::class)->write('recharge.completed', '充值到账', $order->user, $order, null, [
                'no' => $order->no,
                'amount' => $order->amount,
                'received_amount' => $order->received_amount,
            ]);

            return true;
        });
    }

    /**
     * 标记支付成功
     *
     * @param  RechargeOrder  $order  充值订单
     * @param  string|null  $paymentNo  第三方支付流水号
     *
     * @throws Exception
     *
     * @return bool 是否成功
     */
    public function markPaid(RechargeOrder $order, ?string $paymentNo = null): bool
    {
        $affected = RechargeOrder::whereKey($order->getKey())
            ->where('status', RechargeOrderStatus::Pending)
            ->update([
                'status' => RechargeOrderStatus::Paid,
                'payment_no' => $paymentNo,
                'paid_at' => now(),
            ]);

        $order->refresh();

        if ($affected === 0) {
            return false;
        }

        service(EventLogService::class)->write('recharge.paid', '充值支付成功', $order->user, $order, null, [
            'no' => $order->no,
            'amount' => $order->amount,
            'payment_no' => $paymentNo,
        ]);

        return true;
    }

    /**
     * 标记支付失败
     *
     * @param  RechargeOrder  $order  充值订单
     *
     * @throws Exception
     *
     * @return bool 是否成功
     */
    public function markFailed(RechargeOrder $order): bool
    {
        $affected = RechargeOrder::whereKey($order->getKey())
            ->where('status', RechargeOrderStatus::Pending)
            ->update(['status' => RechargeOrderStatus::Failed]);

        $order->refresh();

        if ($affected === 0) {
            return false;
        }

        service(EventLogService::class)->write('recharge.failed', '充值支付失败', null, $order, null, [
            'no' => $order->no,
            'amount' => $order->amount,
        ]);

        return true;
    }
}
