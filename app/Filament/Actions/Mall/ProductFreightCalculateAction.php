<?php

namespace App\Filament\Actions\Mall;

use App\Models\Mall\Product;
use App\Models\Mall\Region;
use App\Models\Mall\Sku;
use App\Services\Mall\DeliveryService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Infolists;
use Filament\Schemas;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;

class ProductFreightCalculateAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'freightCalculate';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('运费计算');
        $this->icon(Heroicon::OutlinedTruck);
        $this->color('warning');

        $this->visible(fn (Product $record): bool => userCan('freightCalculate', $record));

        $this->modalHeading('运费试算');
        $this->modalSubmitAction(false);
        $this->modalCancelActionLabel('关闭');

        $this->schema(static function (Product $record): array {
            $provinces = Region::where('level', 'p')->pluck('name', 'id');
            $skus = $record->skus()->orderByDesc('sort')->pluck('name', 'id');

            return [
                Group::make([
                    Forms\Components\Select::make('province_id')
                        ->label('省份')
                        ->placeholder('选择省份')
                        ->options($provinces)
                        ->searchable()
                        ->reactive(),
                    Forms\Components\Select::make('city_id')
                        ->label('城市')
                        ->placeholder('选择城市')
                        ->options(fn (Get $get) => Region::where('parent_id', $get('province_id'))->pluck('name', 'id'))
                        ->searchable()
                        ->reactive()
                        ->dehydrated(),
                    Forms\Components\Select::make('district_id')
                        ->label('区县')
                        ->placeholder('选择区县')
                        ->options(fn (Get $get) => Region::where('parent_id', $get('city_id'))->pluck('name', 'id'))
                        ->searchable()
                        ->reactive()
                        ->dehydrated(),
                ])
                    ->columns(3)
                    ->columnSpanFull(),
                Forms\Components\Select::make('sku_id')
                    ->label('商品规格')
                    ->options($skus)
                    ->searchable()
                    ->required()
                    ->columnSpan(2),
                Forms\Components\TextInput::make('qty')
                    ->label('购买数量')
                    ->numeric()
                    ->minValue(1)
                    ->default(1)
                    ->required()
                    ->columnSpan(1),
                Schemas\Components\Section::make()
                    ->icon(Heroicon::OutlinedCurrencyDollar)
                    ->schema([
                        Schemas\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('result')
                                    ->label('运费结果')
                                    ->state(fn () => '点击计算运费后显示')
                                    ->placeholder('-')
                                    ->columnSpan(1),
                                Infolists\Components\TextEntry::make('rule')
                                    ->label('计费规则')
                                    ->state(fn (Product $record): string => $record->delivery?->name ?? '暂无运费模板')
                                    ->placeholder('-')
                                    ->columnSpan(1),
                            ]),
                    ])
                    ->columnSpanFull(),
            ];
        });

        $this->modalFooterActions([
            Action::make('calculate')
                ->label('计算运费')
                ->icon(Heroicon::OutlinedCalculator)
                ->color('primary')
                ->action(function (Product $record, array $data): void {
                    $freight = $this->calculateFreight($record, $data);

                    $this->fillForm([
                        'result' => $freight.' 元',
                    ]);
                }),
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
    private function calculateFreight(Product $record, array $data): string
    {
        $delivery = $record->delivery;

        if (!$delivery) {
            return '0.00';
        }

        $skuId = $data['sku_id'] ?? null;

        if (!$skuId) {
            return '0.00';
        }

        /** @var Sku|null $sku */
        $sku = $record->skus()->find($skuId);

        if (!$sku) {
            return '0.00';
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
