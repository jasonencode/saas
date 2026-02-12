<?php

namespace App\Enums;

use App\Extensions\BlockChain\Adapters\AntAdapter;
use App\Extensions\BlockChain\Adapters\BscAdapter;
use App\Extensions\BlockChain\Adapters\Chain33Adapter;
use App\Extensions\BlockChain\Adapters\EthAdapter;
use App\Extensions\BlockChain\Adapters\FiscoAdapter;
use App\Extensions\BlockChain\Adapters\ParaAdapter;
use App\Extensions\BlockChain\Adapters\TronAdapter;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ChainType: string implements HasLabel, HasColor
{
    case Fisco = 'fisco';

    case Ant = 'ant';

    case Ethereum = 'ethereum';

    case Chain33 = 'chain33';

    case Para = 'para';

    case Tron = 'tron';

    case Bsc = 'bsc';

    public function getLabel(): string
    {
        return match ($this) {
            self::Ethereum => '以太坊',
            self::Tron => '波场',
            self::Fisco => '飞梭',
            self::Ant => '蚂蚁链',
            self::Bsc => '币安链',
            self::Chain33 => '复杂美',
            self::Para => '平行链',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Ethereum, self::Bsc, self::Chain33, self::Para => 'info',
            self::Tron => 'danger',
            self::Fisco => 'success',
            self::Ant => 'warning',
        };
    }

    public function getAdapter(): string
    {
        return match ($this) {
            self::Ethereum => EthAdapter::class,
            self::Tron => TronAdapter::class,
            self::Fisco => FiscoAdapter::class,
            self::Ant => AntAdapter::class,
            self::Bsc => BscAdapter::class,
            self::Chain33 => Chain33Adapter::class,
            self::Para => ParaAdapter::class,
        };
    }
}
