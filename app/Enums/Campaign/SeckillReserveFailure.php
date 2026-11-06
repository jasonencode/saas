<?php

namespace App\Enums\Campaign;

use Filament\Support\Contracts\HasLabel;

/**
 * 秒杀名额预扣失败原因
 */
enum SeckillReserveFailure: string implements HasLabel
{
    case NotStarted = 'not_started';

    case Ended = 'ended';

    case Cancelled = 'cancelled';

    case SoldOut = 'sold_out';

    case OverLimit = 'over_limit';

    case NotReady = 'not_ready';

    public function getLabel(): string
    {
        return match ($this) {
            self::NotStarted => '秒杀尚未开始',
            self::Ended => '秒杀已结束',
            self::Cancelled => '秒杀活动已取消',
            self::SoldOut => '商品已售罄',
            self::OverLimit => '超出限购数量',
            self::NotReady => '秒杀系统繁忙，请稍后再试',
        };
    }

    /**
     * 建议的 HTTP 业务错误码
     */
    public function errorCode(): string
    {
        return match ($this) {
            self::NotStarted => 'SECKILL_NOT_STARTED',
            self::Ended => 'SECKILL_ENDED',
            self::Cancelled => 'SECKILL_CANCELLED',
            self::SoldOut => 'SECKILL_SOLD_OUT',
            self::OverLimit => 'SECKILL_LIMIT_EXCEEDED',
            self::NotReady => 'SECKILL_NOT_READY',
        };
    }
}
