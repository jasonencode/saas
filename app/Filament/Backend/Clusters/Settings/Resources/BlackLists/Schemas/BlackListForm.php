<?php

namespace App\Filament\Backend\Clusters\Settings\Resources\BlackLists\Schemas;

use App\Rules\IpOrCidr;
use Filament\Forms;
use Filament\Schemas\Schema;

class BlackListForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Forms\Components\TextInput::make('ip')
                    ->label('IP/CIDR')
                    ->helperText('支持单独IP:172.16.1.1或CIDR:172.16.0.0/16格式')
                    ->rules(['required', new IpOrCidr])
                    ->required(),
                Forms\Components\Textarea::make('remark')
                    ->label('备注')
                    ->rows(4),
            ]);
    }
}
