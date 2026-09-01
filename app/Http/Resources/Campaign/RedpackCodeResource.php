<?php

namespace App\Http\Resources\Campaign;

use App\Http\Resources\Traits\HasDateTimeFormat;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RedpackCodeResource extends JsonResource
{
    use HasDateTimeFormat;

    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->code,
            'amount' => (float) $this->amount,
            'status' => $this->status->value,
            'status_label' => $this->status->getLabel(),
            'claimed_at' => $this->formatDateTime($this->claimed_at),
            'redpack' => RedpackResource::make($this->whenLoaded('redpack')),
            'created_at' => $this->formatDateTime($this->created_at),
        ];
    }
}
