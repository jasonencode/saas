<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RealnameResource extends JsonResource
{
    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'realname_id' => $this->resource->id,
            'type' => $this->resource->type?->value,
            'type_label' => $this->resource->type?->getLabel(),
            'status' => $this->resource->status?->value,
            'status_label' => $this->resource->status?->getLabel(),
            'name' => $this->resource->name,
            'id_card_number' => $this->resource->id_card_number,
            'id_card_front' => $this->resource->id_card_front_url,
            'id_card_back' => $this->resource->id_card_back_url,
            'business_license' => $this->resource->business_license_url,
            'contact_person' => $this->resource->contact_person,
            'contact_phone' => $this->resource->contact_phone,
            'reject_reason' => $this->resource->reject_reason,
            'verified_at' => $this->resource->verified_at,
            'created_at' => $this->resource->created_at,
        ];
    }
}
