<?php

namespace App\Http\Resources\User;

use App\Http\Resources\Mall\OrderResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 发票申请资源
 *
 * 包含关联的发票抬头与订单列表。
 */
class InvoiceApplicationResource extends JsonResource
{
    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'application_id' => $this->resource->id,
            'amount' => $this->resource->amount,
            'reason' => $this->resource->reason,
            'remark' => $this->resource->remark,
            'status' => [
                'value' => $this->resource->status->value,
                'label' => $this->resource->status->getLabel(),
            ],
            'invoice_title' => new InvoiceTitleResource($this->whenLoaded('invoiceTitle')),
            'orders' => OrderResource::collection($this->whenLoaded('orders')),
            'created_at' => (string) $this->resource->created_at,
        ];
    }
}
