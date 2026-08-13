<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Configures\Schemas;

use App\Enums\Mall\AutoCompleteDays;
use App\Filament\Forms\Components\AddressSelect;
use App\Filament\Forms\Components\CustomUpload;
use App\Models\Mall\Express;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Schema;

class ConfigureForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Schemas\Components\Section::make('基础信息')
                    ->columns(3)
                    ->schema([
                        Schemas\Components\Grid::make(1)
                            ->columnSpan(2)
                            ->schema([
                                Forms\Components\TextInput::make('store_name')
                                    ->label('店铺名称')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('store_description')
                                    ->label('店铺描述')
                                    ->maxLength(255)
                                    ->rows(5),
                            ]),
                        CustomUpload::make()
                            ->label('店铺LOGO'),
                    ]),
                Schemas\Components\Section::make('配置')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('default_express_id')
                            ->label('默认发货快递')
                            ->options(fn () => Express::bySort()->pluck('name', 'id'))
                            ->preload()
                            ->searchable(),
                        Forms\Components\Select::make('auto_complete_days')
                            ->label('自动完成天数')
                            ->options(AutoCompleteDays::class)
                            ->preload()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                $expiredMinutes = $get('order_expired_minutes');
                                if ($state && $expiredMinutes) {
                                    $autoCompleteMinutes = $state * 24 * 60;
                                    if ($expiredMinutes >= $autoCompleteMinutes) {
                                        $set('order_expired_minutes', min(1440, max(3, (int) ($autoCompleteMinutes / 2))));
                                    }
                                }
                            }),
                        Forms\Components\TextInput::make('order_expired_minutes')
                            ->label('订单自动取消时间')
                            ->integer()
                            ->minValue(3)
                            ->default(60)
                            ->maxValue(1440)
                            ->suffix('分钟')
                            ->afterStateUpdated(function ($state, $set, $get) {
                                $autoCompleteDays = $get('auto_complete_days');
                                if ($autoCompleteDays && $state) {
                                    $autoCompleteMinutes = $autoCompleteDays * 24 * 60;
                                    if ($state >= $autoCompleteMinutes) {
                                        $set('order_expired_minutes', min(1440, max(3, (int) ($autoCompleteMinutes / 2))));
                                    }
                                }
                            }),
                    ]),
                Schemas\Components\Section::make('联系方式')
                    ->columns()
                    ->schema([
                        Forms\Components\TextInput::make('contactor')
                            ->label('联系人'),
                        Forms\Components\TextInput::make('phone')
                            ->label('电话'),
                        AddressSelect::make(),
                    ]),
            ]);
    }
}
