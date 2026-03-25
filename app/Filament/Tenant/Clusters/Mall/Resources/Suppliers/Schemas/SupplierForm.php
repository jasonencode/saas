<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Suppliers\Schemas;

use App\Filament\Forms\Components\CustomUpload;
use Filament\Forms;
use Filament\Schemas\Schema;

class SupplierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label('供应商名称')
                    ->required(),
                CustomUpload::make()
                    ->label('图片'),
                Forms\Components\Textarea::make('remark')
                    ->label('供应商描述')
                    ->rows(5),
                Forms\Components\TextInput::make('sort')
                    ->label(__('backend.sort'))
                    ->integer()
                    ->default(0),
                Forms\Components\Toggle::make('status')
                    ->label(__('backend.status')),
            ]);
    }
}
