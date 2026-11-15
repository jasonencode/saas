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
        $maxRefundQty = $this->resource->getMaxRefundCounts();

        return [
            'item_id' => $this->resource->id,
            'orderable' => $this->formatOrderable(),
            'qty' => $this->resource->qty,
            'refunded_qty' => max(0, (int) $this->resource->qty - $maxRefundQty),
            'max_refund_qty' => $maxRefundQty,
            'price' => $this->resource->price,
            'sub_total' => $this->resource->sub_total,
            'coupon_discount' => $this->resource->coupon_discount,
            'remark' => $this->resource->remark ?? '',
        ];
    }

    /**
     * 格式化可订购主体数据
     */
    protected function formatOrderable(): array
    {
        $orderable = $this->resource->resolveOrderable();

        // 商品/身份被删除时回退到订单明细上的名称与类型快照，保证历史订单仍可展示
        $data = [
            'type' => $orderable?->getMorphClass() ?? $this->resource->orderable_type,
            'name' => $orderable?->getOrderableName() ?? ($this->resource->orderable_name ?? ''),
            'spec' => $orderable?->getOrderableSpec() ?? '',
            'cover' => $orderable?->getCover() ?? '',
        ];

        if ($orderable instanceof Sku) {
            $data['target_id'] = $orderable->product_id;
        } else {
            $data['target_id'] = $orderable?->getKey() ?? $this->resource->orderable_id;
        }

        return $data;
    }
}
