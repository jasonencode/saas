<?php

namespace App\Enums;

use App\Enums\Traits\EnumMethods;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ExamineState: string implements HasLabel, HasColor, HasIcon
{
    use EnumMethods;

    case Pending = 'pending';

    case Approved = 'approved';

    case Rejected = 'rejected';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Pending => '审核中',
            self::Approved => '已通过',
            self::Rejected => '被驳回',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pending => 'info',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Pending => 'heroicon-o-clock',
            self::Approved => 'heroicon-o-check-circle',
            self::Rejected => 'heroicon-o-x-circle',
        };
    }

    /**
     * 是否为终态
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::Approved, self::Rejected], true);
    }

    /**
     * 是否可以重新提交
     */
    public function canResubmit(): bool
    {
        return $this === self::Rejected;
    }

    /**
     * 检查状态转换是否有效
     */
    public function canTransitionTo(self $newState): bool
    {
        return in_array($newState, $this->getNextStates(), true);
    }

    /**
     * 获取下一步可能的状态
     *
     * @return array<self>
     */
    public function getNextStates(): array
    {
        return match ($this) {
            self::Pending => [self::Approved, self::Rejected],
            default => [],
        };
    }
}
