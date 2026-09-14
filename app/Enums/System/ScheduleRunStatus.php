<?php

namespace App\Enums\System;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ScheduleRunStatus: string implements HasColor, HasLabel
{
    case Running = 'running';

    case Success = 'success';

    case Failed = 'failed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Running => '执行中',
            self::Success => '成功',
            self::Failed => '失败',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Running => 'info',
            self::Success => 'success',
            self::Failed => 'danger',
        };
    }
}
