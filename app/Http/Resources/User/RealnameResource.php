<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

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
            'id_card_number_masked' => $this->mask($this->resource->id_card_number),
            'id_card_front' => $this->image($this->resource->id_card_front),
            'id_card_back' => $this->image($this->resource->id_card_back),
            'business_license' => $this->image($this->resource->business_license),
            'contact_person' => $this->resource->contact_person,
            'contact_phone' => $this->resource->contact_phone,
            'reject_reason' => $this->resource->reject_reason,
            'verified_at' => $this->resource->verified_at,
            'created_at' => $this->resource->created_at,
        ];
    }

    /**
     * 脱敏身份证号：保留前 4 位与后 4 位
     */
    protected function mask(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $value = (string) $value;
        $length = strlen($value);

        if ($length <= 8) {
            return $value;
        }

        return substr($value, 0, 4).str_repeat('*', $length - 8).substr($value, -4);
    }

    /**
     * 图片存储路径转访问 URL
     */
    protected function image(?string $path): ?string
    {
        return blank($path) ? null : Storage::url($path);
    }
}
