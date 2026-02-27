<?php

namespace App\Filament\Backend\Clusters\Foundation\Resources\Aliyuns\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class AliyunForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label('账户名称')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('app_id')
                    ->label('Access Key ID')
                    ->required()
                    ->helperText(new HtmlString('AliyunCDNFullAccess<br/>AliyunOSSFullAccess<br/>AliyunDomainFullAccess<br/>AliyunDNSFullAccess<br/>AliyunDCDNFullAccess')),
                Forms\Components\TextInput::make('app_secret')
                    ->label('Access Key Secret')
                    ->required(),
                Forms\Components\Toggle::make('status')
                    ->label('状态'),
            ]);
    }
}
