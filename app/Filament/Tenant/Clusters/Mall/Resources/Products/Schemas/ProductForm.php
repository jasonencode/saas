<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Products\Schemas;

use App\Enums\Mall\DeductStockType;
use App\Enums\Mall\FulfillmentType;
use App\Filament\Forms\Components\CustomUpload;
use App\Models\Mall\Delivery;
use App\Models\Mall\ReturnAddress;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Schemas\Components\Grid::make(1)
                    ->columnSpan(2)
                    ->schema([
                        Section::make('基本信息')
                            ->columnSpanFull()
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('商品名称')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                Forms\Components\Textarea::make('description')
                                    ->label('商品简介')
                                    ->maxLength(500)
                                    ->rows(4)
                                    ->columnSpanFull(),
                            ]),
                        Section::make('商品图片')
                            ->columnSpanFull()
                            ->collapsible()
                            ->schema([
                                Schemas\Components\Grid::make()
                                    ->schema([
                                        CustomUpload::cover(),
                                        CustomUpload::pictures(),
                                    ]),
                                CustomUpload::pictures('materials', '详情图片集')
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Schemas\Components\Grid::make(1)
                    ->schema([
                        Section::make('分类信息')
                            ->columns()
                            ->schema([
                                SelectTree::make('category_id')
                                    ->label('商品分类')
                                    ->relationship(
                                        relationship: 'category',
                                        titleAttribute: 'name',
                                        parentAttribute: 'parent_id',
                                        modifyQueryUsing: fn (Builder $query) => $query->ofEnabled()->bySort(),
                                        modifyChildQueryUsing: fn (Builder $query) => $query->ofEnabled()->bySort(),
                                    )
                                    ->defaultOpenLevel(2)
                                    ->withCount()
                                    ->searchable()
                                    ->required()
                                    ->columnSpanFull(),
                                Forms\Components\Select::make('tags')
                                    ->label('标签')
                                    ->relationship('tags', 'name', fn ($query) => $query->orderBy('sort'))
                                    ->multiple()
                                    ->preload()
                                    ->searchable()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label('标签名称')
                                            ->required(),
                                    ])
                                    ->columnSpanFull(),
                                Forms\Components\Select::make('brand_id')
                                    ->label('品牌')
                                    ->relationship(
                                        name: 'brand',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn (Builder $query) => $query->ofEnabled()->bySort(),
                                    )
                                    ->searchable()
                                    ->preload(),
                                Forms\Components\Select::make('supplier_id')
                                    ->label('供应商')
                                    ->relationship(
                                        name: 'supplier',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn (Builder $query) => $query->ofEnabled()->bySort(),
                                    )
                                    ->searchable()
                                    ->preload(),
                                Forms\Components\KeyValue::make('ext')
                                    ->label('扩展信息')
                                    ->addActionLabel('添加')
                                    ->reorderable()
                                    ->columnSpanFull(),
                            ]),
                        Section::make('配送信息')
                            ->columns()
                            ->schema([
                                Forms\Components\CheckboxList::make('fulfillment_type')
                                    ->label('履约方式')
                                    ->options(fn () => collect(FulfillmentType::cases())
                                        ->reject(fn (FulfillmentType $type) => $type === FulfillmentType::Virtual)
                                        ->mapWithKeys(fn (FulfillmentType $type) => [$type->value => $type->getLabel()])
                                        ->all())
                                    ->required()
                                    ->columns()
                                    ->default([FulfillmentType::Mail->value])
                                    ->live()
                                    ->helperText('可多选：快递邮寄按运费模板计费，门店自提免运费'),
                                Forms\Components\Radio::make('deduct_stock_type')
                                    ->label('库存扣减方式')
                                    ->options(DeductStockType::class)
                                    ->required()
                                    ->default(DeductStockType::Ordered),
                                Forms\Components\Select::make('delivery_id')
                                    ->label('运费模板')
                                    ->options(fn () => Delivery::bySort()
                                        ->get()
                                        ->mapWithKeys(fn (Delivery $delivery) => [
                                            $delivery->id => "$delivery->name [{$delivery->type->getLabel()}]",
                                        ]))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->default(fn () => Delivery::where('is_default', true)->value('id'))
                                    ->placeholder('选择运费模板')
                                    ->visible(fn (Get $get): bool => in_array(FulfillmentType::Mail->value, $get('fulfillment_type') ?? [], true)),
                                Forms\Components\Select::make('return_address_id')
                                    ->label('退货地址')
                                    ->options(fn () => ReturnAddress::ofEnabled()
                                        ->orderByDesc('is_default')
                                        ->bySort()
                                        ->get()
                                        ->mapWithKeys(fn (ReturnAddress $address) => [
                                            $address->id => sprintf(
                                                '%s %s',
                                                $address->name,
                                                $address->is_default ? '（默认）' : '',
                                            ),
                                        ]))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->default(fn () => ReturnAddress::where('is_default', true)->value('id'))
                                    ->placeholder('选择退货地址')
                                    ->visible(fn (Get $get): bool => in_array(FulfillmentType::Mail->value, $get('fulfillment_type') ?? [], true)),
                            ]),
                        Section::make('辅助信息')
                            ->columns(3)
                            ->schema([
                                Forms\Components\Toggle::make('can_cart')
                                    ->label('可加入购物车')
                                    ->default(true),
                                Forms\Components\TextInput::make('sort')
                                    ->label(__('backend.sort'))
                                    ->integer()
                                    ->required()
                                    ->default(0)
                                    ->helperText('数字越大越靠前'),
                                Forms\Components\TextInput::make('views')
                                    ->label('浏览量')
                                    ->integer()
                                    ->default(0)
                                    ->minValue(0),
                            ]),
                    ]),
                self::skuSection(),
            ]);
    }

    public static function skuSection(): Section
    {
        return Section::make('商品规格')
            ->columnSpanFull()
            ->collapsible()
            ->collapsed(false)
            ->schema([
                Forms\Components\Repeater::make('skus')
                    ->label('商品规格')
                    ->hiddenLabel()
                    ->relationship()
                    ->columns(1)
                    ->defaultItems(1)
                    ->addActionLabel('添加规格')
                    ->reorderable()
                    ->orderColumn()
                    ->reorderableWithButtons()
                    ->columnSpanFull()
                    ->schema([
                        Schemas\Components\Grid::make(7)
                            ->schema([
                                CustomUpload::cover('cover', '规格封面图')
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('name')
                                    ->label('规格名称')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('code')
                                    ->label('规格编号(69码)')
                                    ->maxLength(32),
                                Forms\Components\TextInput::make('weight')
                                    ->label('重量')
                                    ->numeric()
                                    ->step(0.01)
                                    ->rule('decimal:0,2')
                                    ->minValue(0)
                                    ->default(0)
                                    ->suffix('kg'),
                                Forms\Components\TextInput::make('volume')
                                    ->label('体积')
                                    ->numeric()
                                    ->step(0.01)
                                    ->rule('decimal:0,2')
                                    ->minValue(0)
                                    ->default(0)
                                    ->suffix('m³'),
                            ]),
                        Schemas\Components\Grid::make(7)
                            ->schema([
                                Forms\Components\TextInput::make('origin_price')
                                    ->label('原价')
                                    ->columnSpan(2)
                                    ->numeric()
                                    ->step(0.01)
                                    ->rule('decimal:0,2')
                                    ->minValue(0)
                                    ->default(0)
                                    ->prefix('¥')
                                    ->suffix('元'),
                                Forms\Components\TextInput::make('price')
                                    ->label('销售价')
                                    ->columnSpan(2)
                                    ->numeric()
                                    ->step(0.01)
                                    ->rule('decimal:0,2')
                                    ->minValue(0)
                                    ->default(0)
                                    ->required()
                                    ->prefix('¥')
                                    ->suffix('元'),
                                Forms\Components\TextInput::make('stock')
                                    ->label('库存')
                                    ->integer()
                                    ->required()
                                    ->minValue(0)
                                    ->default(0),
                                Forms\Components\TextInput::make('sale')
                                    ->label('销量')
                                    ->integer()
                                    ->minValue(0)
                                    ->default(0),
                                Forms\Components\TextInput::make('sort')
                                    ->label(__('backend.sort'))
                                    ->integer()
                                    ->required()
                                    ->default(0),
                            ]),
                    ]),
            ]);
    }
}
