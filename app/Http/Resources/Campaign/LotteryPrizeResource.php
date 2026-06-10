<?php

namespace App\Http\Resources\Campaign;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LotteryPrizeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'lottery_prize_id' => $this->resource->id,
            'name' => $this->name,
            'type' => $this->type->value,
            'type_label' => $this->type->getLabel(),
            'cover' => $this->cover,
            'weight' => $this->weight,
            'total_quantity' => $this->total_quantity,
            'remaining_quantity' => $this->remaining_quantity,
            'user_limit' => $this->user_limit,
            'sort' => $this->sort,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
