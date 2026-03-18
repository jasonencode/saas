<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Banners\Schemas;

use App\Filament\Forms\Components\CustomUpload;
use App\Filament\Forms\Components\TenantSelect;
use Filament\Forms;
use Filament\Schemas\Schema;

class BannerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TenantSelect::make(),
                Forms\Components\TextInput::make('title')
                    ->label('横幅标题')
                    ->required(),
                CustomUpload::cover()
                    ->required(),
                Forms\Components\TextInput::make('jump')
                    ->label('跳转链接'),
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
