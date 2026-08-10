<?php

namespace App\Support\Tasks;

use App\Contracts\SettlementTask;
use App\Contracts\SettleTaskData;
use App\Enums\Finance\AccountAssetType;
use App\Services\Finance\UserAccountService;
use App\Support\Tasks\Traits\WithDefaultSetting;
use Closure;

class DirectReward implements SettlementTask
{
    use WithDefaultSetting;

    protected array $options = [
        'amount' => 1,
        'asset' => 'points',
    ];

    public function getTitle(): string
    {
        return '直推奖励';
    }

    public function getDescription(): string
    {
        return '直推奖励，推荐人直接获得奖励';
    }

    public function handle(SettleTaskData $data, Closure $next): mixed
    {
        $user = $data->voucher->user;
        $parent = $user->relation->parent;

        if ($parent && $parent->exists) {
            $account = $parent->account;
            if ($account) {
                $asset = AccountAssetType::from($this->options['asset']);
                $amount = (float) $this->options['amount'];

                service(UserAccountService::class)->modifyAsset(
                    account: $account,
                    asset: $asset,
                    amount: $amount,
                    remark: "直推奖励 - 来自用户 {$user->getKey()}",
                    source: $data->voucher,
                );
            }
        }

        return $next($data);
    }
}
