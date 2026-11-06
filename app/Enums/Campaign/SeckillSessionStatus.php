<?php

namespace App\Enums\Campaign;

use Carbon\CarbonInterface;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * 秒杀场次状态
 */
enum SeckillSessionStatus: string implements HasColor, HasLabel
{
    /**
     * 待开始
     */
    case Scheduled = 'scheduled';

    /**
     * 进行中
     */
    case Running = 'running';

    /**
     * 已结束（到点或售罄）
     */
    case Ended = 'ended';

    /**
     * 已取消（人工下线）
     */
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Scheduled => '待开始',
            self::Running => '进行中',
            self::Ended => '已结束',
            self::Cancelled => '已取消',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Scheduled => 'info',
            self::Running => 'success',
            self::Ended => 'gray',
            self::Cancelled => 'danger',
        };
    }

    /**
     * 是否允许购买（仅进行中）
     */
    public function isPurchasable(): bool
    {
        return $this === self::Running;
    }

    /**
     * 按时间推断当前应处的状态
     */
    public static function fromTime(CarbonInterface $startAt, CarbonInterface $endAt, ?CarbonInterface $now = null): self
    {
        $now ??= now();

        if ($now->lt($startAt)) {
            return self::Scheduled;
        }

        return $now->gt($endAt) ? self::Ended : self::Running;
    }
}
