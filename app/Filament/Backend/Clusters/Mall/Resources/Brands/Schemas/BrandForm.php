<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Brands\Schemas;

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
                Forms\Components\TextInput::make('name')
                    ->label('品牌名称')
                    ->required()
                    ->columnSpanFull(),
                TenantSelect::make(),
                CustomUpload::cover()
                    ->label('品牌图标'),
                Forms\Components\KeyValue::make('ext')
                    ->label('自定义键值')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('sort')
                    ->label(__('backend.sort'))
                    ->integer()
                    ->default(0)
                    ->helperText('数字越大越靠前'),
                Forms\Components\Textarea::make('description')
                    ->label('品牌描述'),
                Forms\Components\Toggle::make('status')
                    ->label(__('backend.status')),
            ]);
    }
}
