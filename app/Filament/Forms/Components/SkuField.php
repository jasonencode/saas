<?php

namespace App\Filament\Forms\Components;

use App\Models\Mall\Attribute;
use App\Models\Mall\AttributeValue;
use App\Models\Mall\Sku;
use Filament\Forms\Components\Field;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class SkuField extends Field
{
    protected string $view = 'fields.sku';

    /**
     * 初始化组件
     */
    protected function setUp(): void
    {
        parent::setUp();

        // 从数据库加载已有数据（编辑模式）
        $this->afterStateHydrated(function (SkuField $component, ?Model $record): void {
            if (!$record || !$record->exists) {
                $component->state([
                    'attrs' => [],
                    'skus' => [],
                ]);

                return;
            }

            $attrs = $record->attributes()->with('values')->get()->map(function (Attribute $attr) {
                return [
                    'id' => $attr->getKey(),
                    'name' => $attr->name,
                    'values' => $attr->values->map(fn (AttributeValue $v) => [
                        'id' => $v->getKey(),
                        'value' => $v->value,
                    ])->values()->all(),
                ];
            })->values()->all();

            $skus = $record->skus()->with('attributes')->get()->map(function (Sku $sku) {
                $spec = $sku->attributes->map(fn ($attr) => [
                    'attribute_id' => $attr->getKey(),
                    'attribute_value_id' => $attr->pivot->attribute_value_id,
                ])->values()->all();

                return [
                    'id' => $sku->getKey(),
                    'cover' => $sku->cover,
                    'price' => $sku->price,
                    'origin_price' => $sku->origin_price,
                    'stock' => $sku->stock,
                    'code' => $sku->code,
                    'spec' => $spec,
                    'spec_key' => self::buildSpecKey($spec),
                ];
            })->values()->all();

            $component->state([
                'attrs' => $attrs,
                'skus' => $skus,
            ]);
        });

        // 保存前不需要特殊处理，dehydrate 为 false 防止写入 products 表
        $this->dehydrated(false);

        // 保存关联数据
        $this->saveRelationshipsUsing(function (SkuField $component, Model $record): void {
            $state = $component->getState() ?? [];
            $attrs = $state['attrs'] ?? [];
            $skusData = $state['skus'] ?? [];

            // 保存属性和属性值，建立 ID 映射
            $attrIdMap = [];   // 临时ID => 数据库ID
            $valueIdMap = [];  // 临时ID => 数据库ID

            // 清理该商品旧属性（级联删除属性值）
            $record->attributes()->delete();

            foreach ($attrs as $attrData) {
                $attribute = $record->attributes()->create([
                    'name' => $attrData['name'],
                ]);
                $attrIdMap[$attrData['id']] = $attribute->getKey();

                foreach ($attrData['values'] as $valData) {
                    $value = $attribute->values()->create([
                        'value' => $valData['value'],
                    ]);
                    $valueIdMap[$valData['id']] = $value->getKey();
                }
            }

            // 清理旧 SKU
            $record->skus()->each(fn (Sku $sku) => $sku->delete());

            // 创建新 SKU
            foreach ($skusData as $skuData) {
                if (empty(Arr::get($skuData, 'price')) && empty(Arr::get($skuData, 'stock'))) {
                    continue;
                }

                /** @var Sku $sku */
                $sku = $record->skus()->create([
                    'cover' => $skuData['cover'] ?? null,
                    'price' => $skuData['price'] ?? 0,
                    'origin_price' => $skuData['origin_price'] ?? 0,
                    'stock' => $skuData['stock'] ?? 0,
                    'code' => $skuData['code'] ?? null,
                ]);

                // 绑定属性
                foreach ($skuData['spec'] as $specItem) {
                    $dbAttrId = $attrIdMap[$specItem['attribute_id']] ?? null;
                    $dbValueId = $valueIdMap[$specItem['attribute_value_id']] ?? null;

                    if ($dbAttrId && $dbValueId) {
                        $sku->attributes()->attach($dbAttrId, [
                            'attribute_value_id' => $dbValueId,
                        ]);
                    }
                }
            }
        });
    }

    /**
     * 根据规格项构建唯一 key，用于比对 SKU 行
     */
    public static function buildSpecKey(array $spec): string
    {
        return collect($spec)
            ->sortBy('attribute_id')
            ->map(fn ($item) => $item['attribute_id'].':'.$item['attribute_value_id'])
            ->implode('-');
    }
}
