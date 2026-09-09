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
            'lottery_draw_id' => $this->resource->lottery_draw_id,
            'lottery_id' => $this->resource->lottery_id,
            'user_id' => $this->resource->user_id,
            'lottery_prize_id' => $this->resource->lottery_prize_id,
            'type' => $this->resource->type->value,
            'type_label' => $this->resource->type->getLabel(),
            'prize_detail' => $this->resource->prize_detail,
            'status' => $this->resource->status->value,
            'status_label' => $this->resource->status->getLabel(),
            'fulfillment_note' => $this->resource->fulfillment_note,
            'fulfilled_at' => $this->resource->fulfilled_at,
            'prize' => LotteryPrizeResource::make($this->resource->prize),
            'created_at' => $this->resource->created_at,
        ];
    }
}
