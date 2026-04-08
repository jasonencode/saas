<?php

namespace App\Tasks;

use App\Contracts\SettlementTask;
use App\Contracts\SettleTaskData;
use App\Tasks\Traits\WithDefaultSetting;
use Closure;

class SecondReward implements SettlementTask
{
    use WithDefaultSetting;

    protected array $options = [
        'goods' => 1,
    ];

    public function getTitle(): string
    {
        return '多层推荐奖励';
    }

    public function getDescription(): string
    {
        return '规则的描述';
    }

    public function handle(SettleTaskData $data, Closure $next): mixed
    {
        $data->addParameter($this->options);

        return $next($data);
    }
}
