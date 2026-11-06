<?php

namespace App\Filament\Forms\Campaign;

use App\Enums\Campaign\SeckillSessionStatus;
use App\Models\Mall\Sku;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class SeckillSessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Schemas\Components\Section::make('基础信息')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('场次名称')
                            ->placeholder('如：10:00 场')
                            ->required()
                            ->maxLength(64),
                        Forms\Components\TextInput::make('seckill_price')
                            ->label('秒杀价')
                            ->numeric()
                            ->minValue(0.01)
                            ->prefix('¥')
                            ->required(),
                        Forms\Components\TextInput::make('stock')
                            ->label('秒杀名额')
                            ->integer()
                            ->minValue(1)
                            ->required(),
                        Forms\Components\TextInput::make('limit_per_user')
                            ->label('每人限购')
                            ->integer()
                            ->minValue(1)
                            ->default(1)
                            ->required(),
                    ]),
                Schemas\Components\Section::make('商品 SKU')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->label('商品')
                            ->relationship(
                                'product',
                                'name',
                                modifyQueryUsing: fn (Builder $query) => $query->when(
                                    filament()->getTenant() !== null,
                                    fn (Builder $query) => $query->where('tenant_id', filament()->getTenant()->getKey()),
                                ),
                            )
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('sku_id', null))
                            ->required(),
                        Forms\Components\Select::make('sku_id')
                            ->label('规格 SKU')
                            ->options(fn (Get $get): array => Sku::query()
                                ->where('product_id', $get('product_id'))
                                ->orderBy('id')
                                ->get()
                                ->mapWithKeys(fn (Sku $sku): array => [
                                    $sku->getKey() => sprintf('%s（价格 %s / 库存 %s）', $sku->name, $sku->price, $sku->stock),
                                ])
                                ->all())
                            ->searchable()
                            ->live()
                            ->required(),
                    ]),
                Schemas\Components\Section::make('时间与状态')
                    ->columns(2)
                    ->schema([
                        Forms\Components\DateTimePicker::make('start_at')
                            ->label('开抢时间')
                            ->displayFormat('Y-m-d H:i:s')
                            ->minDate(fn (string $operation) => $operation === 'create' ? now() : null)
                            ->live()
                            ->required(),
                        Forms\Components\DateTimePicker::make('end_at')
                            ->label('结束时间')
                            ->displayFormat('Y-m-d H:i:s')
                            ->minDate(fn (Get $get) => $get('start_at'))
                            ->after('start_at')
                            ->validationMessages(['after' => '结束时间必须晚于开抢时间'])
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label('场次状态')
                            ->options(SeckillSessionStatus::class)
                            ->default(SeckillSessionStatus::Scheduled)
                            ->disabled(fn (string $operation): bool => $operation === 'create')
                            ->required(),
                        Forms\Components\TextInput::make('sort')
                            ->label('排序')
                            ->integer()
                            ->default(0),
                    ]),
            ]);
    }
}
