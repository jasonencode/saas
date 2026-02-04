<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Expresses\Schemas;

use App\Filament\Forms\Components\CustomUpload;
use Filament\Forms;
use Filament\Schemas\Schema;

class ExpressForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label('物流名称')
                    ->required(),
                Forms\Components\TextInput::make('code')
                    ->label('编码')
                    ->required()
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('phone')
                    ->label('客服电话'),
                CustomUpload::cover()
                    ->label('LOGO'),
                Forms\Components\TextInput::make('sort')
                    ->label('排序')
                    ->integer()
                    ->default(0)
                    ->helperText('数字越大越靠前'),
                Forms\Components\Toggle::make('status')
                    ->label('状态'),
            ]);
    }
}

