<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Banners\Schemas;

use App\Filament\Forms\Components\CustomUpload;
use Filament\Forms;
use Filament\Schemas\Schema;

class BannerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
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
