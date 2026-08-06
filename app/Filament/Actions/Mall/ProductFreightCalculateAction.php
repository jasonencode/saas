<?php

namespace App\Filament\Actions\Mall;

use App\Models\Mall\Product;
use App\Models\Mall\Region;
use App\Models\Mall\Sku;
use App\Services\Mall\DeliveryService;
use Filament\Actions\Action;
use Filament\Forms;
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

        $this->modalHeading('运费试算');
        $this->modalSubmitActionLabel('计算运费');
        $this->modalCancelAction(false);

        $this->schema(static function (Product $record): array {
            // 地址三级下拉一次性加载，避免 live 重渲染反复查库
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
                Forms\Components\Placeholder::make('freight')
                    ->label('运费')
                    ->content(fn (Get $get) => sprintf('%s 元', $get('freight') ?? '尚未计算'))
                    ->columnSpanFull(),
            ];
        });

        $this->action(function (Product $record, array $data): void {
            $freight = $this->calculateFreight($record, $data);

            // 把结果写回表单状态，Placeholder 即在当前 modal 内显示
            $this->fillForm(['freight' => $freight]);

            $this->successNotificationTitle(sprintf('运费：%s 元', $freight));
            $this->success();
        });
    }

    /**
     * 计算运费
     *
     * @param  Product  $record  当前商品
     * @param  array  $data  表单数据（sku_id / qty / province_id / city_id / district_id）
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

        $items = new Collection([
            (object) [
                'qty' => max(1, (int) ($data['qty'] ?? 1)),
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
