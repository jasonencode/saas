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
        // 证件号脱敏：仅保留首尾各 1 位；证件照 URL 属敏感 PII，用户端接口不再下发（后台详情页可见）
        $idCardNumber = $this->resource->id_card_number;

        return [
            'realname_id' => $this->resource->id,
            'type' => $this->resource->type?->value,
            'type_label' => $this->resource->type?->getLabel(),
            'status' => $this->resource->status?->value,
            'status_label' => $this->resource->status?->getLabel(),
            'name' => $this->resource->name,
            'id_card_number' => $idCardNumber
                ? mb_substr($idCardNumber, 0, 1).'****'.mb_substr($idCardNumber, -1)
                : null,
            'id_card_front' => null,
            'id_card_back' => null,
            'business_license' => $this->resource->business_license_url,
            'contact_person' => $this->resource->contact_person,
            'contact_phone' => $this->resource->contact_phone,
            'reject_reason' => $this->resource->reject_reason,
            'verified_at' => $this->resource->verified_at,
            'created_at' => $this->resource->created_at,
        ];
    }
}
