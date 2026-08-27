<?php

namespace App\Http\Resources\Mall;

use App\Http\Resources\BaseCollection;
use App\Http\Resources\Traits\HasDateTimeFormat;
use Illuminate\Http\Request;

class OrderCollection extends BaseCollection
{
    use HasDateTimeFormat;

    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'list' => $this->collection->map(function ($item) {
                return [
                    'order_id' => $item->id,
                    'no' => $item->no,
                    'status' => [
                        'value' => $item->status->value,
                        'label' => $item->status->getLabel(),
                    ],
                    'total_amount' => $item->total_amount,
                    'amount' => $item->amount,
                    'freight' => $item->freight,
                    'items' => OrderItemResource::collection($item->items),
                    'expired_at' => $this->formatDateTime($item->expired_at),
                    'paid_at' => $this->formatDateTime($item->paid_at),
                    'signed_at' => $this->formatDateTime($item->signed_at),
                    'created_at' => $this->formatDateTime($item->created_at),
                ];
            }),
            'page' => $this->pagination(),
        ];
    }
}
