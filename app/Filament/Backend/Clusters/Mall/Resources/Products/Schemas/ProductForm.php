<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Products\Schemas;

use App\Filament\Forms\Components\CustomUpload;
use App\Filament\Forms\Components\SkuField;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\DeductStockType;
use App\Enums\ProductContentType;
use App\Enums\ProductStatus;

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
                            TextInput::make('name')
                                ->label('商品名称')
                                ->required(),
                            Textarea::make('description')
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
                            Radio::make('content_type')
                                ->label('详情类型')
                                ->options(ProductContentType::class)
                                ->default(ProductContentType::Material)
                                ->inline()
                                ->live()
                                ->required()
                                ->inlineLabel(false),
                            CustomUpload::make('materials')
                                ->label('详情图集')
                                ->multiple()
                                ->visible(fn(Get $get) => $get('content_type') == ProductContentType::Material->value || $get('content_type') == ProductContentType::Material)
                                ->columnSpanFull(),
                            RichEditor::make('content')
                                ->label('详情富文本')
                                ->visible(fn(Get $get) => $get('content_type') == ProductContentType::RichText->value || $get('content_type') == ProductContentType::RichText)
                                ->grow()
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
                        Select::make('store_id')
                            ->label('所属店铺')
                            ->required()
                            ->native(false)
                            ->relationship(
                                name: 'store',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn($query) => $query->ofEnabled()
                            )
                            ->live()
                            ->searchable()
                            ->preload()
                            ->columnSpanFull(),
                        SelectTree::make('categories')
                            ->label('分类')
                            ->relationship(
                                relationship: 'categories',
                                titleAttribute: 'name',
                                parentAttribute: 'parent_id',
                                modifyQueryUsing: function (Builder $query, Get $get) {
                                    if (config('mall.category_in_one')) {
                                        return $query->ofEnabled()->disableCache();
                                    }

                                    return $query->ofStore($get('store_id'))->ofEnabled()->disableCache();
                                },
                                modifyChildQueryUsing: function (Builder $query, Get $get) {
                                    if (config('mall.category_in_one')) {
                                        return $query->ofEnabled()->disableCache();
                                    }

                                    return $query->ofStore($get('store_id'))->ofEnabled()->disableCache();
                                },
                            )
                            ->dehydrated(false)
                            ->required()
                            ->searchable()
                            ->withCount(),
                        Select::make('brand_id')
                            ->label('品牌')
                            ->native(false)
                            ->relationship(
                                name: 'brand',
                                titleAttribute: 'name',
                                modifyQueryUsing: function (Builder $query, Get $get) {
                                    if (config('mall.category_in_one')) {
                                        return $query->ofEnabled();
                                    }

                                    if ($get('store_id')) {
                                        return $query->ofStore($get('store_id'))->ofEnabled();
                                    }

                                    return $query->whereNull('id')->ofEnabled();
                                },
                            )
                            ->searchable()
                            ->preload(),
                        KeyValue::make('ext')
                            ->label('扩展信息')
                            ->columnSpanFull(),
                        Radio::make('status')
                            ->label('商品状态')
                            ->options(ProductStatus::class)
                            ->default(ProductStatus::Up)
                            ->inline()
                            ->inlineLabel(false),
                        Toggle::make('can_cart')
                            ->label('可加入购物车')
                            ->inline(false)
                            ->inlineLabel(false),
                        TextInput::make('sort')
                            ->label('排序')
                            ->required()
                            ->default(0)
                            ->helperText('数字越大越靠前')
                            ->integer(),
                        Radio::make('deduct_stock_type')
                            ->label('库存扣减方式')
                            ->inline()
                            ->inlineLabel(false)
                            ->options(DeductStockType::class)
                            ->default(DeductStockType::Paid),
                        TextInput::make('views')
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

