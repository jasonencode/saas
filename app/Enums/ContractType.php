<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ContractType: string implements HasLabel
{
    case CUSTOM = 'custom';

    case ERC20 = 'erc20';

    case ERC721 = 'erc721';

    case ERC1155 = 'erc1155';

    public function getLabel(): string
    {
        return match ($this) {
            self::CUSTOM => '普通合约',
            self::ERC20 => 'ERC20',
            self::ERC721 => 'ERC721',
            self::ERC1155 => 'ERC1155',
        };
    }
}
