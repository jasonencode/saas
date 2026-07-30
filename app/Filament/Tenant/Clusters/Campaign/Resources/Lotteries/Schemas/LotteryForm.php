<?php

namespace App\Filament\Tenant\Clusters\Campaign\Resources\Lotteries\Schemas;

use App\Enums\Campaign\LotteryDrawMode;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class LotteryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Schemas\Components\Fieldset::make('基础信息')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('活动名称')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Radio::make('draw_mode')
                            ->label('抽奖模式')
                            ->options(LotteryDrawMode::class)
                            ->live()
                            ->required(),
                        Forms\Components\FileUpload::make('cover')
                            ->label('活动封面')
                            ->image()
                            ->directory('lottery/covers'),
                        Forms\Components\Textarea::make('description')
                            ->label('活动描述')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                Schemas\Components\Fieldset::make('抽奖配置')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('free_draws_per_day')
                            ->label('每日免费次数')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->visible(fn (Get $get) => $get('draw_mode') === LotteryDrawMode::Free),
                        Forms\Components\TextInput::make('points_per_draw')
                            ->label('每次消耗积分')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->prefix('积分')
                            ->visible(fn (Get $get) => $get('draw_mode') === LotteryDrawMode::Points),
                        Forms\Components\TextInput::make('max_draws_per_user')
                            ->label('每人总抽奖次数上限')
                            ->numeric()
                            ->nullable()
                            ->minValue(1)
                            ->placeholder('不限'),
                    ]),
                Schemas\Components\Fieldset::make('活动时间')
                    ->columnSpanFull()
                    ->columns()
                    ->schema([
                        Forms\Components\DateTimePicker::make('start_at')
                            ->label('开始时间'),
                        Forms\Components\DateTimePicker::make('end_at')
                            ->label('结束时间'),
                    ]),
                Forms\Components\Toggle::make('status')
                    ->label(__('backend.status')),
            ]);
    }
}
