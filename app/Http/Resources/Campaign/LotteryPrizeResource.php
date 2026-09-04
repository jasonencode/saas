<?php

namespace App\Http\Resources\Campaign;

use App\Http\Resources\Traits\HasDateTimeFormat;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LotteryPrizeResource extends JsonResource
{
    use HasDateTimeFormat;

    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'lottery_prize_id' => $this->resource->id,
            'name' => $this->resource->name,
            'type' => $this->resource->type->value,
            'type_label' => $this->resource->type->getLabel(),
            'cover' => $this->resource->cover,
            'weight' => $this->resource->weight,
            'total_quantity' => $this->resource->total_quantity,
            'remaining_quantity' => $this->resource->remaining_quantity,
            'user_limit' => $this->resource->user_limit,
            'sort' => $this->resource->sort,
            'created_at' => $this->formatDateTime($this->resource->created_at),
        ];
    }
}
