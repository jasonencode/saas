<?php

namespace App\Enums\BlockChain;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ContractDeployStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';

    case Deploying = 'deploying';

    case Deployed = 'deployed';

    case Failed = 'failed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => '待部署',
            self::Deploying => '部署中',
            self::Deployed => '已部署',
            self::Failed => '部署失败',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Deploying => 'warning',
            self::Deployed => 'success',
            self::Failed => 'danger',
        };
    }
}
