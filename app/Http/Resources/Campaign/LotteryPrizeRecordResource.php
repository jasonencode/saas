<?php

namespace App\Http\Resources\Campaign;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LotteryPrizeRecordResource extends JsonResource
{
    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'lottery_prize_record_id' => $this->resource->id,
            'lottery_draw_id' => $this->lottery_draw_id,
            'lottery_id' => $this->lottery_id,
            'user_id' => $this->user_id,
            'lottery_prize_id' => $this->lottery_prize_id,
            'type' => $this->type->value,
            'type_label' => $this->type->getLabel(),
            'prize_detail' => $this->prize_detail,
            'status' => $this->status->value,
            'status_label' => $this->status->getLabel(),
            'fulfillment_note' => $this->fulfillment_note,
            'fulfilled_at' => $this->fulfilled_at?->toDateTimeString(),
            'prize' => new LotteryPrizeResource($this->whenLoaded('prize')),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
