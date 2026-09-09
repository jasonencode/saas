<?php

namespace App\Http\Resources\Campaign;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LotteryDrawResource extends JsonResource
{
    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'lottery_draw_id' => $this->resource->id,
            'lottery_id' => $this->resource->lottery_id,
            'user_id' => $this->resource->user_id,
            'lottery_prize_id' => $this->resource->lottery_prize_id,
            'draw_cost_type' => $this->resource->draw_cost_type,
            'draw_cost_amount' => (float) $this->resource->draw_cost_amount,
            'ip_address' => $this->resource->ip_address,
            'prize' => LotteryPrizeResource::make($this->resource->prize),
            'prize_record' => LotteryPrizeRecordResource::make($this->whenLoaded('prizeRecord')),
            'created_at' => $this->resource->created_at,
        ];
    }
}
