<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoucherResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'voucher_id' => $this->resource->id,
            'no' => $this->resource->no,
            'plan_name' => $this->resource->relationLoaded('plan') ? $this->resource->plan?->name : null,
            'status' => $this->resource->status?->value,
            'status_label' => $this->resource->status?->getLabel(),
            'target_type' => $this->resource->target_type,
            'target_id' => $this->resource->target_id,
            'scheduled_at' => $this->resource->scheduled_at,
            'completed_at' => $this->resource->completed_at,
            'created_at' => (string) $this->resource->created_at,
        ];
    }
}
