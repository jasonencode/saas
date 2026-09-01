<?php

namespace App\Http\Resources\User;

use App\Http\Resources\EnumResource;
use App\Http\Resources\Traits\HasDateTimeFormat;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 发票资源
 */
class InvoiceResource extends JsonResource
{
    use HasDateTimeFormat;

    public function toArray(Request $request): array
    {
        return [
            'invoice_id' => $this->resource->id,
            'invoice_no' => $this->resource->invoice_no,
            'invoice_date' => $this->formatDate($this->resource->invoice_date),
            'type' => EnumResource::make($this->resource->type),
            'amount' => $this->resource->amount,
            'status' => EnumResource::make($this->resource->status),
            'recipient_email' => $this->resource->recipient_email,
            'recipient_phone' => $this->resource->recipient_phone,
            'remark' => $this->resource->remark,
            'creator' => $this->resource->creator,
            'application' => InvoiceApplicationResource::make($this->whenLoaded('application')),
            'created_at' => (string) $this->resource->created_at,
        ];
    }
}
