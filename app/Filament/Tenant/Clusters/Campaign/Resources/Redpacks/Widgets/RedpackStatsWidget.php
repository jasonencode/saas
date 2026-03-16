<?php

namespace App\Filament\Tenant\Clusters\Campaign\Resources\Redpacks\Widgets;

use App\Enums\RedpackCodeStatus;
use App\Models\Redpack;
use Filament\Widgets\StatsOverviewWidget;
use Illuminate\Database\Eloquent\Model;

class RedpackStatsWidget extends StatsOverviewWidget
{
    public ?Model $record = null;

    protected function getStats(): array
    {
        if (!$this->record instanceof Redpack) {
            return [];
        }

        $totalAmount = $this->record->codes()->sum('amount');
        $claimedAmount = $this->record->codes()
            ->where('status', RedpackCodeStatus::Claimed)
            ->sum('amount');
        $unclaimedAmount = $this->record->codes()
            ->where('status', RedpackCodeStatus::Active)
            ->sum('amount');

        return [
            StatsOverviewWidget\Stat::make('总金额', '￥'.$totalAmount)
                ->description('该红包活动下所有码的总金额')
                ->color('info'),
            StatsOverviewWidget\Stat::make('已领取金额', '￥'.$claimedAmount)
                ->description('状态为“已领取”的金额')
                ->color('success'),
            StatsOverviewWidget\Stat::make('未领取金额', '￥'.$unclaimedAmount)
                ->description('状态为“待领取”的金额')
                ->color('warning'),
        ];
    }
}
