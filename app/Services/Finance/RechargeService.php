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
        if ($order->status !== RechargeOrderStatus::Paid) {
            throw new InvalidArgumentException('充值订单状态不正确');
        }

        $account = UserAccount::firstOrCreate(
            ['user_id' => $order->user_id],
            [
                'balance' => 0,
                'frozen_balance' => 0,
                'points' => 0,
                'frozen_points' => 0,
            ]
        );

        $accountService = service(UserAccountService::class);

        return DB::transaction(static function () use ($account, $accountService, $order) {
            $asset = $order->type === RechargeOrderType::Balance
                ? AccountAssetType::Balance
                : AccountAssetType::Points;

            $accountService->modifyAsset(
                account: $account,
                asset: $asset,
                amount: $order->received_amount,
                remark: "充值单号# {$order->no}",
                source: $order,
                type: UserAccountLogType::Recharge,
            );

            $order->update([
                'status' => RechargeOrderStatus::Completed,
                'completed_at' => now(),
            ]);

            return true;
        });
    }

    /**
     * 取消充值订单
     *
     * @param  RechargeOrder  $order  充值订单
     *
     * @throws Exception
     *
     * @return bool 是否成功
     */
    public function cancel(RechargeOrder $order): bool
    {
        if (!in_array($order->status, [RechargeOrderStatus::Pending, RechargeOrderStatus::Processing])) {
            throw new InvalidArgumentException('充值订单状态不可取消');
        }

        return $order->update(['status' => RechargeOrderStatus::Canceled]);
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
        if ($order->status !== RechargeOrderStatus::Pending) {
            throw new InvalidArgumentException('充值订单状态不正确');
        }

        return $order->update([
            'status' => RechargeOrderStatus::Paid,
            'payment_no' => $paymentNo,
            'paid_at' => now(),
        ]);
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
        if ($order->status !== RechargeOrderStatus::Pending) {
            throw new InvalidArgumentException('充值订单状态不正确');
        }

        return $order->update(['status' => RechargeOrderStatus::Failed]);
    }
}
