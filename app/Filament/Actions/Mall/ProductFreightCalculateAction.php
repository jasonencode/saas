<?php

namespace App\Filament\Actions\Mall;

use App\Enums\Mall\RegionLevel;
use App\Models\Mall\Product;
use App\Models\Mall\Region;
use App\Models\Mall\Sku;
use App\Services\Mall\DeliveryService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Infolists;
use Filament\Schemas;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;

class ProductFreightCalculateAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'productFreightCalculate';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('运费计算');
        $this->icon(Heroicon::OutlinedTruck);
        $this->color('warning');

        $this->visible(fn (Product $record): bool => userCan(self::getDefaultName(), $record));

        $this->modalHeading('运费试算');
        $this->modalSubmitAction(false);
        $this->modalCancelActionLabel('关闭');

        $this->fillForm(function (Product $record) {
            return [
                'qty' => 1,
                'sku_id' => $record->skus()->bySort()->first()?->id,
            ];
        });

        $this->schema([
            Schemas\Components\Section::make('邮寄地址')
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('province_id')
                        ->label('省份')
                        ->placeholder('选择省份')
                        ->options(fn () => Region::where('level', RegionLevel::Province)->bySort()->pluck('name', 'id'))
                        ->searchable()
                        ->reactive()
                        ->live(onBlur: true),
                    Forms\Components\Select::make('city_id')
                        ->label('城市')
                        ->placeholder('选择城市')
                        ->options(fn (Get $get) => Region::where('parent_id', $get('province_id'))->bySort()->pluck('name', 'id'))
                        ->searchable()
                        ->reactive()
                        ->dehydrated()
                        ->live(onBlur: true),
                    Forms\Components\Select::make('district_id')
                        ->label('区县')
                        ->placeholder('选择区县')
                        ->options(fn (Get $get) => Region::where('parent_id', $get('city_id'))->bySort()->pluck('name', 'id'))
                        ->searchable()
                        ->reactive()
                        ->dehydrated()
                        ->live(onBlur: true),
                ]),
            Schemas\Components\Section::make('商品规格')
                ->columns()
                ->schema([
                    Forms\Components\Select::make('sku_id')
                        ->label('商品规格')
                        ->options(fn (Product $record) => $record->skus()->bySort()->pluck('name', 'id'))
                        ->required()
                        ->live(onBlur: true),
                    Forms\Components\TextInput::make('qty')
                        ->label('购买数量')
                        ->numeric()
                        ->minValue(1)
                        ->default(1)
                        ->required()
                        ->columnSpan(1)
                        ->live(onBlur: true),
                ]),
            Schemas\Components\Section::make('运费计算')
                ->columns()
                ->schema([
                    Infolists\Components\TextEntry::make('rule')
                        ->label('计费规则')
                        ->state(fn (Product $record): string => $record->delivery?->name ?? '暂无运费模板')
                        ->placeholder('-'),
                    Forms\Components\Placeholder::make('freight_result')
                        ->label('运费结果')
                        ->content(fn (Get $get, Product $record): string => match (true) {
                            blank($get('sku_id')) => '请选择商品规格',
                            default => self::calculateFreight($record, [
                                'sku_id' => $get('sku_id'),
                                'qty' => $get('qty'),
                                'province_id' => $get('province_id'),
                                'city_id' => $get('city_id'),
                                'district_id' => $get('district_id'),
                            ]).' 元',
                        }),
                ]),
        ]);
    }

    /**
     * 计算运费
     *
     * @param  Product  $record  当前商品
     * @param  array  $data  表单数据
     *
     * @return string 运费金额（保留两位小数）
     */
    private static function calculateFreight(Product $record, array $data): string
    {
        $delivery = $record->delivery;

        if (!$delivery) {
            return '0.00';
        }

        $skuId = $data['sku_id'] ?? null;

        if (!$skuId) {
            return '-';
        }

        /** @var Sku|null $sku */
        $sku = $record->skus()->find($skuId);

        if (!$sku) {
            return '-';
        }

        $qty = max(1, (int) ($data['qty'] ?? 1));

        $items = new Collection([
            (object) [
                'qty' => $qty,
                'sku' => $sku,
            ],
        ]);

        /** @var DeliveryService $service */
        $service = app(DeliveryService::class);

        return $service->calculateOrderFreight(
            delivery: $delivery,
            items: $items,
            provinceId: isset($data['province_id']) ? (int) $data['province_id'] : null,
            cityId: isset($data['city_id']) ? (int) $data['city_id'] : null,
            districtId: isset($data['district_id']) ? (int) $data['district_id'] : null,
        );
    }
}
