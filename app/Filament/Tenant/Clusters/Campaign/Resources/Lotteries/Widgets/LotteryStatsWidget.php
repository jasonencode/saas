<?php

namespace App\Filament\Tenant\Clusters\Campaign\Resources\Lotteries\Widgets;

use App\Enums\Campaign\LotteryPrizeStatus;
use App\Models\Campaign\Lottery;
use Filament\Widgets\StatsOverviewWidget;
use Illuminate\Database\Eloquent\Model;

class LotteryStatsWidget extends StatsOverviewWidget
{
    public ?Model $record = null;

    protected function getStats(): array
    {
        if (!$this->record instanceof Lottery) {
            return [];
        }

        $totalDraws = $this->record->draws()->count();
        $totalWins = $this->record->prizeRecords()->count();
        $winRate = $totalDraws > 0 ? round($totalWins / $totalDraws * 100, 1).'%' : '0%';
        $pendingPhysical = $this->record->prizeRecords()
            ->where('type', 'physical')
            ->where('status', LotteryPrizeStatus::Pending)
            ->count();

        return [
            StatsOverviewWidget\Stat::make('总抽奖次数', number_format($totalDraws))
                ->description('该活动的总抽奖次数')
                ->color('info'),
            StatsOverviewWidget\Stat::make('总中奖次数', number_format($totalWins))
                ->description('该活动的总中奖次数')
                ->color('success'),
            StatsOverviewWidget\Stat::make('中奖率', $winRate)
                ->description('中奖次数 / 抽奖次数')
                ->color('primary'),
            StatsOverviewWidget\Stat::make('待兑奖实物', $pendingPhysical)
                ->description('待处理的实物奖品')
                ->color('warning'),
        ];
    }
}
