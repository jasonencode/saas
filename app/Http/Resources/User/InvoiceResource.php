<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 发票资源
 */
class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'invoice_id' => $this->resource->id,
            'invoice_no' => $this->resource->invoice_no,
            'invoice_date' => $this->resource->invoice_date?->toDateString(),
            'type' => [
                'value' => $this->resource->type->value,
                'label' => $this->resource->type->getLabel(),
            ],
            'amount' => $this->resource->amount,
            'status' => [
                'value' => $this->resource->status->value,
                'label' => $this->resource->status->getLabel(),
            ],
            'recipient_email' => $this->resource->recipient_email,
            'recipient_phone' => $this->resource->recipient_phone,
            'remark' => $this->resource->remark,
            'creator' => $this->resource->creator,
            'application' => new InvoiceApplicationResource($this->whenLoaded('application')),
            'created_at' => (string) $this->resource->created_at,
        ];
    }
}
