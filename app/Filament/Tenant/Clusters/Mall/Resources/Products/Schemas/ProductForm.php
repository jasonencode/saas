<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Products\Schemas;

use App\Enums\Mall\DeductStockType;
use App\Enums\Mall\ProductStatus;
use App\Filament\Forms\Components\CustomUpload;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Wizard::make([
                    Wizard\Step::make('SKU配置')
                        ->components([
                            Forms\Components\Repeatable::make('skus')
                                ->label('商品规格')
                                ->relationship()
                                ->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->label('规格名称')
                                        ->placeholder('如：红色/L')
                                        ->required(),
                                    Forms\Components\TextInput::make('code')
                                        ->label('商品编码')
                                        ->placeholder('条形码/SKU编号'),
                                    Forms\Components\TextInput::make('price')
                                        ->label('销售价')
                                        ->numeric()
                                        ->required()
                                        ->suffix('元'),
                                    Forms\Components\TextInput::make('origin_price')
                                        ->label('市场价')
                                        ->numeric()
                                        ->suffix('元'),
                                    Forms\Components\TextInput::make('stock')
                                        ->label('库存')
                                        ->numeric()
                                        ->required()
                                        ->default(0),
                                    Forms\Components\TextInput::make('sale')
                                        ->label('销量')
                                        ->numeric()
                                        ->default(0)
                                        ->hidden(),
                                ])
                                ->columns(3)
                                ->defaultItems(0)
                                ->addActionLabel('添加规格')
                                ->reorderable()
                                ->columnSpanFull(),
                        ]),
                    Wizard\Step::make('base')
                        ->label('商品信息')
                        ->components([
                            Forms\Components\TextInput::make('name')
                                ->label('商品名称')
                                ->required(),
                            Forms\Components\Textarea::make('description')
                                ->label('商品简介')
                                ->rows(4)
                                ->columnSpanFull(),
                            CustomUpload::cover()
                                ->label('封面图'),
                            CustomUpload::pictures()
                                ->label('轮播图'),
                            CustomUpload::make('materials')
                                ->label('详情图集')
                                ->multiple()
                                ->columnSpanFull(),
                        ]),
                ])
                    ->columnSpan(2),
                Section::make('扩展信息')
                    ->components([
                        SelectTree::make('category_id')
                            ->label('分类')
                            ->relationship(
                                relationship: 'category',
                                titleAttribute: 'name',
                                parentAttribute: 'parent_id',
                                modifyQueryUsing: fn (Builder $query) => $query->ofEnabled(),
                                modifyChildQueryUsing: fn (Builder $query) => $query->ofEnabled(),
                            )
                            ->required()
                            ->searchable()
                            ->withCount(),
                        Forms\Components\Select::make('brand_id')
                            ->label('品牌')
                            ->native(false)
                            ->relationship(
                                name: 'brand',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query) => $query->ofEnabled()
                            )
                            ->searchable()
                            ->preload(),
                        Forms\Components\KeyValue::make('ext')
                            ->label('扩展信息')
                            ->columnSpanFull(),
                        Forms\Components\Radio::make('status')
                            ->label('商品状态')
                            ->options(ProductStatus::class)
                            ->default(ProductStatus::Up),
                        Forms\Components\Toggle::make('can_cart')
                            ->label('可加入购物车'),
                        Forms\Components\TextInput::make('sort')
                            ->label(__('backend.sort'))
                            ->required()
                            ->default(0)
                            ->helperText('数字越大越靠前')
                            ->integer(),
                        Forms\Components\Radio::make('deduct_stock_type')
                            ->label('库存扣减方式')
                            ->options(DeductStockType::class)
                            ->default(DeductStockType::Paid),
                        Forms\Components\TextInput::make('views')
                            ->label('浏览量')
                            ->integer()
                            ->default(0)
                            ->required(),
                    ]),
            ]);
    }
}
