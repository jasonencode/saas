<?php

namespace App\Services\Finance;

use App\Contracts\ServiceInterface;
use App\Enums\Finance\AccountAssetType;
use App\Enums\User\UserAccountLogType;
use App\Models\Finance\UserAccount;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Throwable;

class UserAccountService implements ServiceInterface
{
    /**
     * 修改用户资产（增加或扣除）
     *
     * @param  UserAccount  $account  用户账户
     * @param  AccountAssetType  $asset  资产类型
     * @param  float  $amount  调整数量（正数增加，负数扣除）
     * @param  string  $remark  备注
     * @param  Model|null  $source  操作来源模型
     * @param  UserAccountLogType  $type  日志类型（默认系统调整）
     *
     * @throws Exception|Throwable 当余额不足时抛出异常
     *
     * @return bool 是否成功
     */
    public function modifyAsset(
        UserAccount $account,
        AccountAssetType $asset,
        float $amount,
        string $remark,
        ?Model $source = null,
        UserAccountLogType $type = UserAccountLogType::System,
    ): bool {
        $field = $asset->getField();

        // 检查余额是否充足（扣除时）
        if ($amount < 0 && $account->$field + $amount < 0) {
            throw new InvalidArgumentException(
                ($asset === AccountAssetType::Balance ? '余额' : '积分').'不足'
            );
        }

        DB::transaction(static function () use ($account, $amount, $asset, $field, $remark, $source, $type) {
            $before = $account->$field;

            if ($amount > 0) {
                $account->increment($field, $amount);
            } else {
                $account->decrement($field, abs($amount));
            }

            $account->refresh();
            $after = $account->$field;

            $account->logs()->create([
                'type' => $type,
                'asset' => $asset,
                'amount' => $amount,
                'before' => $before,
                'after' => $after,
                'remark' => $remark,
                'source_type' => $source?->getMorphClass(),
                'source_id' => $source?->getKey(),
            ]);
        });

        return true;
    }

    /**
     * 冻结/解冻用户资产
     *
     * @param  UserAccount  $account  用户账户
     * @param  AccountAssetType  $asset  资产类型
     * @param  float  $amount  操作数量
     * @param  bool  $isFreeze  是否冻结（true=冻结，false=解冻）
     * @param  string  $remark  备注
     * @param  Model|null  $source  操作来源模型
     *
     * @throws Exception|Throwable 当资产不足时抛出异常
     *
     * @return bool 是否成功
     */
    public function frozenAsset(
        UserAccount $account,
        AccountAssetType $asset,
        float $amount,
        bool $isFreeze,
        string $remark,
        ?Model $source = null
    ): bool {
        $field = $asset->getField();
        $frozenField = match ($asset) {
            AccountAssetType::Balance => 'frozen_balance',
            AccountAssetType::Points => 'frozen_points',
        };

        // 检查资产是否充足
        if ($isFreeze && $amount > $account->$field) {
            throw new InvalidArgumentException(
                ($asset === AccountAssetType::Balance ? '可用余额' : '可用积分').'不足'
            );
        }

        if (!$isFreeze && $amount > $account->$frozenField) {
            throw new InvalidArgumentException(
                ($asset === AccountAssetType::Balance ? '冻结余额' : '冻结积分').'不足'
            );
        }

        return $this->processFreezeOrUnfreeze(
            account: $account,
            amount: $amount,
            field: $field,
            frozenField: $frozenField,
            isFreeze: $isFreeze,
            remark: $remark,
            source: $source
        );
    }

    /**
     * 处理冻结或解冻操作
     *
     * @param  UserAccount  $account  用户账户
     * @param  float  $amount  操作数量
     * @param  string  $field  主资产字段
     * @param  string  $frozenField  冻结资产字段
     * @param  bool  $isFreeze  是否冻结
     * @param  string  $remark  备注
     * @param  Model|null  $source  操作来源模型
     *
     * @throws Throwable
     *
     * @return bool 是否成功
     */
    protected function processFreezeOrUnfreeze(
        UserAccount $account,
        float $amount,
        string $field,
        string $frozenField,
        bool $isFreeze,
        string $remark,
        ?Model $source = null
    ): bool {
        DB::transaction(static function () use ($account, $amount, $field, $frozenField, $isFreeze, $remark, $source) {
            $before = $account->$field;

            if ($isFreeze) {
                $account->decrement($field, $amount);
                $account->increment($frozenField, $amount);
                $logAmount = -$amount;
            } else {
                $account->decrement($frozenField, $amount);
                $account->increment($field, $amount);
                $logAmount = $amount;
            }

            $account->refresh();
            $after = $account->$field;

            $account->logs()->create([
                'type' => $isFreeze ? UserAccountLogType::Freeze : UserAccountLogType::Unfreeze,
                'asset' => AccountAssetType::fromField($field),
                'amount' => $logAmount,
                'before' => $before,
                'after' => $after,
                'remark' => $remark,
                'source_type' => $source?->getMorphClass(),
                'source_id' => $source?->getKey(),
                'extra' => [
                    'frozen_before' => $isFreeze ? $account->$frozenField - $amount : $account->$frozenField + $amount,
                    'frozen_after' => $account->$frozenField,
                ],
            ]);
        });

        return true;
    }

    /**
     * 是否已设置支付密码
     *
     * @param  UserAccount  $account  用户账户
     *
     * @return bool 是否已设置
     */
    public function hasPaymentPassword(UserAccount $account): bool
    {
        return $account->payment_password !== null;
    }

    /**
     * 验证支付密码
     *
     * @param  UserAccount  $account  用户账户
     * @param  string  $password  支付密码
     *
     * @return bool 是否验证通过
     */
    public function verifyPaymentPassword(UserAccount $account, string $password): bool
    {
        if (!$account->payment_password) {
            throw new InvalidArgumentException('请先设置支付密码');
        }

        return Hash::check($password, $account->payment_password);
    }

    /**
     * 设置支付密码
     *
     * @param  UserAccount  $account  用户账户
     * @param  string  $password  新支付密码
     *
     * @return bool 是否设置成功
     */
    public function setPaymentPassword(UserAccount $account, string $password): bool
    {
        $account->update(['payment_password' => $password]);

        return true;
    }

    /**
     * 修改支付密码
     *
     * @param  UserAccount  $account  用户账户
     * @param  string  $oldPassword  旧支付密码
     * @param  string  $newPassword  新支付密码
     *
     * @return bool 是否修改成功
     */
    public function changePaymentPassword(UserAccount $account, string $oldPassword, string $newPassword): bool
    {
        if (!$this->verifyPaymentPassword($account, $oldPassword)) {
            throw new InvalidArgumentException('原支付密码不正确');
        }

        return $this->setPaymentPassword($account, $newPassword);
    }
}
