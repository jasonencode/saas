<?php

namespace App\Filament\Backend\Clusters\User\Resources\Identities\Schemas;

use App\Filament\Forms\Components\CustomUpload;
use App\Filament\Forms\Components\TenantSelect;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Schema;

class IdentityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TenantSelect::make(),
                Schemas\Components\Section::make('基本信息')
                    ->columns()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('身份名称')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('price')
                            ->label('订阅价格')
                            ->numeric()
                            ->prefix('¥')
                            ->default(0),
                        Forms\Components\Textarea::make('description')
                            ->label('简介')
                            ->rows(3)
                            ->columnSpanFull(),
                        CustomUpload::cover()
                            ->columnSpanFull(),
                    ]),
                Schemas\Components\Section::make('身份配置')
                    ->columns()
                    ->schema([
                        Forms\Components\Toggle::make('status')
                            ->label('状态（是否启用）')
                            ->default(true),
                        Forms\Components\Toggle::make('is_default')
                            ->label('默认身份'),
                        Forms\Components\Toggle::make('is_unique')
                            ->label('唯一身份')
                            ->helperText('订阅后不允许订阅其他身份'),
                        Forms\Components\Toggle::make('can_subscribe')
                            ->label('可订阅'),
                        Forms\Components\TextInput::make('days')
                            ->label('有效期（天）')
                            ->integer()
                            ->default(0)
                            ->helperText('0 表示永久'),
                        Forms\Components\TextInput::make('sort')
                            ->label(__('backend.sort'))
                            ->integer()
                            ->default(0)
                            ->helperText('数字越大越靠前'),
                    ]),
                Schemas\Components\Section::make('身份编号')
                    ->columns()
                    ->collapsed()
                    ->schema([
                        Forms\Components\Toggle::make('serial_open')
                            ->label('开启身份编号')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('serial_prefix')
                            ->label('编号前缀')
                            ->maxLength(16),
                        Forms\Components\TextInput::make('serial_places')
                            ->label('编号位数')
                            ->integer()
                            ->default(0),
                        Forms\Components\TextInput::make('serial_reserve')
                            ->label('预留编号数量')
                            ->integer()
                            ->default(0),
                    ]),
            ]);
    }
}
