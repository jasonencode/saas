<?php

namespace App\Support\Tasks;

use App\Contracts\SettlementTask;
use App\Contracts\SettleTaskData;
use App\Enums\Finance\AccountAssetType;
use App\Services\Finance\UserAccountService;
use App\Support\Tasks\Traits\WithDefaultSetting;
use Closure;

class SecondReward implements SettlementTask
{
    use WithDefaultSetting;

    protected array $options = [
        'amount' => 1,
        'asset' => 'points',
    ];

    public function getTitle(): string
    {
        return '二级推荐奖励';
    }

    public function getDescription(): string
    {
        return '二级推荐奖励，推荐人的上级获得奖励';
    }

    public function handle(SettleTaskData $data, Closure $next): mixed
    {
        $user = $data->voucher->user;
        $ancestor = $user->relation->getAncestorAtLayer(2);

        if ($ancestor && $ancestor->exists) {
            $account = $ancestor->account;
            if ($account) {
                $asset = AccountAssetType::from($this->options['asset']);
                $amount = (float) $this->options['amount'];

                service(UserAccountService::class)->modifyAsset(
                    account: $account,
                    asset: $asset,
                    amount: $amount,
                    remark: "二级推荐奖励 - 来自用户 {$user->getKey()}",
                    source: $data->voucher,
                );
            }
        }

        return $next($data);
    }
}
