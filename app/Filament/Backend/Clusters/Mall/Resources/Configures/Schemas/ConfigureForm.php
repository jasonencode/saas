<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Configures\Schemas;

use App\Filament\Forms\Components\AddressSelect;
use App\Filament\Forms\Components\CustomUpload;
use App\Models\Express;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;

class ConfigureForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make('基础信息')
                    ->components([
                        Forms\Components\TextInput::make('store_name')
                            ->label('店铺名称')
                            ->required()
                            ->maxLength(255),
                        CustomUpload::make()
                            ->label('店铺LOGO')
                            ->avatar(),
                        Forms\Components\Textarea::make('store_description')
                            ->label('店铺描述')
                            ->maxLength(255)
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
                Fieldset::make('配置')
                    ->components([
                        Forms\Components\Select::make('default_express_id')
                            ->label('默认发货快递')
                            ->options(fn () => Express::bySort()->pluck('name', 'id'))
                            ->preload()
                            ->searchable(),
                    ]),
                Fieldset::make('联系方式')
                    ->components([
                        Forms\Components\TextInput::make('contactor')
                            ->label('联系人'),
                        Forms\Components\TextInput::make('phone')
                            ->label('电话'),
                    ]),
                Fieldset::make('地址信息')
                    ->components([
                        AddressSelect::make(),
                    ]),
            ]);
    }
}
