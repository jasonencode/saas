<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Orders\Schemas;

use App\Filament\Tables\Configurations\AddressSelectTable;
use App\Filament\Tables\Configurations\ProductSelectTable;
use App\Models\Mall\Product;
use App\Models\Mall\ProductSku;
use App\Models\Tenant\Address;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Illuminate\Support\Number;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Schemas\Components\Section::make('商品信息')
                    ->columnSpan(2)
                    ->components([
                        Forms\Components\Repeater::make('items')
                            ->hiddenLabel()
                            ->required()
                            ->addActionLabel('添加商品')
                            ->compact()
                            ->defaultItems(1)
                            ->minItems(1)
                            ->reorderable(false)
                            ->table([
                                Forms\Components\Repeater\TableColumn::make('商品')
                                    ->markAsRequired(),
                                Forms\Components\Repeater\TableColumn::make('规格')
                                    ->width('200px')
                                    ->markAsRequired(),
                                Forms\Components\Repeater\TableColumn::make('数量')
                                    ->width('100px')
                                    ->markAsRequired(),
                                Forms\Components\Repeater\TableColumn::make('单价')
                                    ->width('100px'),
                                Forms\Components\Repeater\TableColumn::make('原价')
                                    ->width('100px'),
                                Forms\Components\Repeater\TableColumn::make('商品备注')
                                    ->width('200px'),
                            ])
                            ->components([
                                Forms\Components\ModalTableSelect::make('product_id')
                                    ->relationship(
                                        name: 'products',
                                        titleAttribute: 'name',
                                    )
                                    ->saveRelationshipsUsing(static function (): void {
                                    })
                                    ->tableConfiguration(ProductSelectTable::class)
                                    ->distinct()
                                    ->selectAction(
                                        fn (Action $action) => $action
                                            ->label('选择商品')
                                            ->modalHeading('选择商品')
                                            ->modalSubmitActionLabel('选择'),
                                    )
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        $product = Product::find($state);
                                        if ($product) {
                                            $set('product_name', $product->name);
                                            $set('price', null);
                                            $set('product_sku_id', null);
                                            $set('origin_price', null);
                                        }
                                    }),
                                Forms\Components\Select::make('product_sku_id')
                                    ->options(function ($get) {
                                        $product = Product::find($get('product_id'));

                                        return $product?->skus->pluck('name', 'id') ?? [];
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        if ($state) {
                                            $sku = ProductSku::find($state);
                                            if ($sku) {
                                                $set('price', $sku->price);
                                                $set('origin_price', $sku->origin_price);
                                            }
                                        }
                                    }),
                                Forms\Components\TextInput::make('qty')
                                    ->required()
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->reactive(),
                                Forms\Components\TextInput::make('price')
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\TextInput::make('origin_price')
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\TextInput::make('remark'),
                            ]),
                    ]),
                Schemas\Components\Grid::make()
                    ->columns(1)
                    ->components([
                        Schemas\Components\Section::make('价格信息')
                            ->columns(3)
                            ->components([
                                TextEntry::make('amount_display')
                                    ->label('商品金额')
                                    ->money('cny')
                                    ->size(TextSize::Large)
                                    ->color('primary')
                                    ->state(function ($get) {
                                        $items = $get('items') ?? [];

                                        return collect($items)->sum(function ($item) {
                                            return bcmul($item['price'] ?? 0, $item['qty'] ?? 0, 2);
                                        });
                                    }),
                                TextEntry::make('shipping_display')
                                    ->label('运费')
                                    ->state(function ($get) {
                                        return Number::currency(0, 'CNY');
                                    }),
                                TextEntry::make('total_display')
                                    ->label('订单总额')
                                    ->state(function ($get) {
                                        $items = $get('items') ?? [];
                                        $amount = collect($items)->sum(function ($item) {
                                            return bcmul($item['price'] ?? 0, $item['qty'] ?? 0, 2);
                                        });

                                        return Number::currency($amount, 'CNY');
                                    }),
                            ]),
                        Schemas\Components\Section::make('收货地址')
                            ->components([
                                Forms\Components\ModalTableSelect::make('address_id')
                                    ->hiddenLabel()
                                    ->relationship(
                                        name: 'addresses',
                                        titleAttribute: 'full_address',
                                    )
                                    ->saveRelationshipsUsing(static function (): void {
                                    })
                                    ->tableConfiguration(AddressSelectTable::class)
                                    ->selectAction(
                                        fn (Action $action) => $action
                                            ->label('选择地址')
                                            ->modalHeading('选择收货地址')
                                            ->modalSubmitActionLabel('选择'),
                                    )
                                    ->placeholder('不选择地址可稍后填写')
                                    ->getOptionLabelFromRecordUsing(
                                        fn (Address $record): string => "{$record->name} {$record->mobile} {$record->full_address}"
                                    ),
                            ]),
                        Schemas\Components\Section::make('订单备注')
                            ->components([
                                Forms\Components\Textarea::make('remark')
                                    ->label('备注')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
