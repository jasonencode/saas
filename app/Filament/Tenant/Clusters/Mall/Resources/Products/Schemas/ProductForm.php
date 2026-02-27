<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Products\Schemas;

use App\Enums\CategoryType;
use App\Enums\DeductStockType;
use App\Enums\ProductStatus;
use App\Filament\Forms\Components\CustomUpload;
use App\Filament\Forms\Components\SkuField;
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
            ->components([
                Wizard::make([
                    Wizard\Step::make('SKU配置')
                        ->schema([
                            SkuField::make('skus')
                                ->label('SKU配置'),
                        ]),
                    Wizard\Step::make('base')
                        ->label('基本信息')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('商品名称')
                                ->required(),
                            Forms\Components\Textarea::make('description')
                                ->label('商品简介')
                                ->rows(4)
                                ->columnSpanFull(),
                            CustomUpload::cover()
                                ->required()
                                ->label('封面图'),
                            CustomUpload::pictures()
                                ->label('轮播图'),
                        ]),
                    Wizard\Step::make('content')
                        ->label('商品详情')
                        ->schema([
                            CustomUpload::make('materials')
                                ->label('详情图集')
                                ->multiple()
                                ->columnSpanFull(),
                        ]),

                ])->columnSpan([
                    'sm' => 1,
                    'md' => 2,
                    'lg' => 2,
                    'xl' => 2,
                    '2xl' => 3,
                ]),
                Section::make('扩展信息')
                    ->schema([
                        SelectTree::make('categories')
                            ->label('分类')
                            ->relationship(
                                relationship: 'categories',
                                titleAttribute: 'name',
                                parentAttribute: 'parent_id',
                                modifyQueryUsing: fn(Builder $query) => $query->where('type', CategoryType::Product)->ofEnabled(),
                                modifyChildQueryUsing: fn(Builder $query) => $query->where('type', CategoryType::Product)->ofEnabled(),
                            )
                            ->dehydrated(false)
                            ->required()
                            ->searchable()
                            ->withCount(),
                        Forms\Components\Select::make('brand_id')
                            ->label('品牌')
                            ->native(false)
                            ->relationship(
                                name: 'brand',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn(Builder $query) => $query->ofEnabled()
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
                            ->label('排序')
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
                    ])->columnSpan([
                        'sm' => 1,
                        'md' => 2,
                        'lg' => 1,
                        'xl' => 1,
                        '2xl' => 1,
                    ]),
            ]);
    }
}

