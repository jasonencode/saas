<?php

namespace App\Filament\Backend\Clusters\Campaign\Resources\Coupons\Schemas;

use App\Enums\CouponType;
use App\Enums\ExpiredType;
use App\Filament\Forms\Components\TenantSelect;
use Filament\Forms;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CouponForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TenantSelect::make(),
                Fieldset::make('基础信息')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('优惠券名称')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('code')
                            ->label('优惠券代码')
                            ->required()
                            ->maxLength(64)
                            ->unique(ignoreRecord: true),
                        Forms\Components\Textarea::make('description')
                            ->label('优惠券描述')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                Fieldset::make('折扣信息')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\Radio::make('type')
                            ->label('优惠券类型')
                            ->options(CouponType::class)
                            ->default(CouponType::Fixed)
                            ->required()
                            ->columnSpanFull()
                            ->live(),
                        Forms\Components\TextInput::make('value')
                            ->label('折扣值')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                        Forms\Components\TextInput::make('min_amount')
                            ->label('最低消费金额')
                            ->numeric()
                            ->minValue(0)
                            ->nullable()
                            ->default(0)
                            ->required(),
                        Forms\Components\TextInput::make('max_discount')
                            ->label('最大折扣金额')
                            ->visible(fn(Get $get) => $get('type') === CouponType::Percent)
                            ->numeric()
                            ->minValue(0)
                            ->nullable()
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('usage_limit')
                            ->label('使用次数限制(发放数量)')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->nullable()
                            ->required(),
                        Forms\Components\TextInput::make('usage_limit_per_user')
                            ->label('每人使用次数限制(限领数量)')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->nullable()
                            ->required(),
                    ]),
                Fieldset::make('有效期信息')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\Radio::make('expired_type')
                            ->label('过期方式')
                            ->options(ExpiredType::class)
                            ->default(ExpiredType::Receive)
                            ->live()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('days')
                            ->label('有效时长')
                            ->visible(fn(Get $get) => $get('expired_type') === ExpiredType::Receive)
                            ->default(0)
                            ->helperText('为0时永不过期')
                            ->suffix('天')
                            ->integer()
                            ->required(),
                        Forms\Components\DateTimePicker::make('start_at')
                            ->label('开始日期')
                            ->visible(fn(Get $get) => $get('expired_type') === ExpiredType::Fixed)
                            ->native(false)
                            ->nullable()
                            ->live()
                            ->displayFormat('Y-m-d H:i:s')
                            ->maxDate(function (Get $get) {
                                return $get('end_at');
                            })
                            ->required(),
                        Forms\Components\DateTimePicker::make('end_at')
                            ->label('结束日期')
                            ->visible(fn(Get $get) => $get('expired_type') === ExpiredType::Fixed)
                            ->native(false)
                            ->nullable()
                            ->live()
                            ->displayFormat('Y-m-d H:i:s')
                            ->minDate(function (Get $get) {
                                return $get('start_at');
                            })
                            ->required(),
                    ]),
                Forms\Components\Toggle::make('status')
                    ->label(__('backend.status'))
                    ->columnSpanFull(),
            ]);
    }
}

