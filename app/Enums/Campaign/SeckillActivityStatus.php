<?php

namespace App\Enums\Campaign;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * 秒杀活动状态
 */
enum SeckillActivityStatus: string implements HasColor, HasLabel
{
    /**
     * 草稿：前台不可见
     */
    case Draft = 'draft';

    /**
     * 已发布：前台可见场次
     */
    case Published = 'published';

    /**
     * 已关闭：只读
     */
    case Closed = 'closed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => '草稿',
            self::Published => '已发布',
            self::Closed => '已关闭',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Published => 'success',
            self::Closed => 'danger',
        };
    }
}
