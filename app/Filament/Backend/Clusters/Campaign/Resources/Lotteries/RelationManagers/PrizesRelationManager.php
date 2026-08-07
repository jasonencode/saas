<?php

namespace App\Filament\Backend\Clusters\Campaign\Resources\Lotteries\RelationManagers;

use App\Enums\Campaign\LotteryPrizeType;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class PrizesRelationManager extends RelationManager
{
    protected static string $relationship = 'prizes';

    protected static ?string $title = '奖品池';

    protected static ?string $modelLabel = '奖品';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label('奖品名称')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->label('奖品类型')
                    ->options(LotteryPrizeType::class)
                    ->live()
                    ->required(),
                Forms\Components\TextInput::make('weight')
                    ->label('权重')
                    ->numeric()
                    ->default(100)
                    ->minValue(1)
                    ->required()
                    ->helperText('中奖概率 = 当前权重 / 所有奖品权重之和，数值越大中奖概率越高'),
                Forms\Components\TextInput::make('total_quantity')
                    ->label('总数量')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->helperText('0 表示无限量'),
                Forms\Components\TextInput::make('remaining_quantity')
                    ->label('剩余数量')
                    ->numeric()
                    ->default(0)
                    ->minValue(0),
                Forms\Components\TextInput::make('user_limit')
                    ->label('每人限领')
                    ->numeric()
                    ->nullable()
                    ->minValue(1)
                    ->placeholder('不限'),
                Forms\Components\TextInput::make('sort')
                    ->label(__('backend.sort'))
                    ->integer()
                    ->required()
                    ->default(0),
                // 奖品配置 JSON
                Forms\Components\TextInput::make('prize_config.amount')
                    ->label('金额')
                    ->numeric()
                    ->prefix('￥')
                    ->visible(fn (Get $get) => in_array($get('type'), [
                        LotteryPrizeType::Balance,
                        LotteryPrizeType::Points,
                        LotteryPrizeType::Redpack,
                    ], true)),
                Forms\Components\TextInput::make('prize_config.coupon_id')
                    ->label('优惠券 ID')
                    ->numeric()
                    ->visible(fn (Get $get) => $get('type') === LotteryPrizeType::Coupon),
                Forms\Components\TextInput::make('prize_config.name')
                    ->label('实物名称')
                    ->visible(fn (Get $get) => $get('type') === LotteryPrizeType::Physical),
                Forms\Components\Textarea::make('prize_config.description')
                    ->label('实物描述')
                    ->rows(3)
                    ->visible(fn (Get $get) => $get('type') === LotteryPrizeType::Physical),
                Forms\Components\Textarea::make('prize_config.delivery_info')
                    ->label('配送信息')
                    ->rows(3)
                    ->visible(fn (Get $get) => $get('type') === LotteryPrizeType::Physical),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('奖品名称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('奖品类型')
                    ->badge(),
                Tables\Columns\TextColumn::make('weight')
                    ->label('权重')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_quantity')
                    ->label('总数量')
                    ->formatStateUsing(fn ($state) => $state === 0 ? '无限' : $state),
                Tables\Columns\TextColumn::make('remaining_quantity')
                    ->label('剩余数量')
                    ->formatStateUsing(fn ($state) => $state === 0 ? '无限' : $state),
                Tables\Columns\TextColumn::make('user_limit')
                    ->label('每人限领')
                    ->formatStateUsing(fn ($state) => $state ?? '不限'),
                Tables\Columns\TextColumn::make('sort')
                    ->label(__('backend.sort'))
                    ->sortable(),
            ])
            ->headerActions([
                Actions\CreateAction::make(),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ]);
    }
}
