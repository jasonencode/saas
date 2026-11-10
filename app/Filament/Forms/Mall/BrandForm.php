<?php

namespace App\Filament\Forms\Mall;

use App\Filament\Forms\Components\CustomUpload;
use App\Filament\Forms\Components\TenantSelect;
use Filament\Forms;
use Filament\Schemas\Schema;

class BrandForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TenantSelect::make()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('name')
                    ->label('品牌名称')
                    ->required()
                    ->columnSpanFull(),
                CustomUpload::cover()
                    ->label('品牌图标'),
                Forms\Components\Textarea::make('description')
                    ->label('品牌描述'),
                Forms\Components\KeyValue::make('ext')
                    ->label('自定义参数')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('sort')
                    ->label(__('backend.sort'))
                    ->required()
                    ->integer()
                    ->default(0)
                    ->helperText('数字越大越靠前'),
                Forms\Components\Toggle::make('status')
                    ->label(__('backend.status')),
            ]);
    }
}
