<?php

namespace App\Tasks;

use App\Models\System;
use Closure;
use App\Contracts\SettlementTask;
use App\Contracts\SettleTaskData;
use App\Tasks\Traits\WithDefaultSetting;

class DirectReward implements SettlementTask
{
    use WithDefaultSetting;

    protected array $options = [
        'amount' => 1,
        'wallet_type' => WalletType::Point->value,
    ];

    public function getTitle(): string
    {
        return '直推奖励';
    }

    public function getDescription(): string
    {
        return '直推奖励，推荐一个用户，直接获取积分';
    }

    public function handle(SettleTaskData $data, Closure $next): mixed
    {
        $user = $data->voucher->user;
        $parent = $user->relation->parent;

        if ($parent) {
            resolve(WalletService::class)
                ->execute(
                    $parent,
                    WalletType::tryFrom($this->options['wallet_type']),
                    $this->options['amount'],
                    false,
                    System::find(1),
                    ['child_id' => $user->id]
                );
        }

        return $next($data);
    }
}