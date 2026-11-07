<?php

namespace App\Http\Resources\Campaign;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LotteryPrizeResource extends JsonResource
{
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
            'user_limit' => $this->resource->user_limit,
            'sort' => $this->resource->sort,
            'created_at' => $this->resource->created_at,
        ];
    }
}
