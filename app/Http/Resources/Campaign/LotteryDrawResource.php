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
            'lottery_id' => $this->lottery_id,
            'user_id' => $this->user_id,
            'lottery_prize_id' => $this->lottery_prize_id,
            'draw_cost_type' => $this->draw_cost_type,
            'draw_cost_amount' => (float) $this->draw_cost_amount,
            'ip_address' => $this->ip_address,
            'prize' => new LotteryPrizeResource($this->whenLoaded('prize')),
            'prize_record' => new LotteryPrizeRecordResource($this->whenLoaded('prizeRecord')),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
