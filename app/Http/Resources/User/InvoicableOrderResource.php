<?php

namespace App\Http\Resources\User;

use App\Http\Resources\Traits\HasDateTimeFormat;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 可开票订单资源
 *
 * 用于发票申请时展示可关联的订单列表，字段精简。
 */
class InvoicableOrderResource extends JsonResource
{
    use HasDateTimeFormat;

    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'order_id' => $this->resource->id,
            'no' => $this->resource->no,
            'status' => [
                'value' => $this->resource->status->value,
                'label' => $this->resource->status->getLabel(),
            ],
            'total_amount' => $this->resource->total_amount,
            'paid_at' => $this->formatDateTime($this->resource->paid_at),
            'created_at' => $this->formatDateTime($this->resource->created_at),
        ];
    }
}
