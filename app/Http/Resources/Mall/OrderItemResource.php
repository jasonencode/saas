<?php

namespace App\Http\Resources\Mall;

use App\Models\Mall\Sku;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'item_id' => $this->resource->id,
            'orderable' => $this->formatOrderable(),
            'qty' => $this->resource->qty,
            'price' => $this->resource->price,
            'sub_total' => $this->resource->sub_total,
            'remark' => $this->resource->remark ?? '',
        ];
    }

    /**
     * 格式化可订购主体数据
     */
    protected function formatOrderable(): array
    {
        $orderable = $this->resource->orderable;

        $data = [
            'type' => $orderable->getMorphClass(),
            'name' => $orderable->getOrderableName(),
            'spec' => $orderable->getOrderableSpec(),
            'cover' => $orderable->getCover(),
        ];

        if ($orderable instanceof Sku) {
            $data['target_id'] = $orderable->product_id;
        } else {
            $data['target_id'] = $orderable->id;
        }

        return $data;
    }
}
