<?php

namespace App\Http\Resources\Mall;

use App\Http\Resources\BaseCollection;
use App\Models\Mall\Order;
use Illuminate\Http\Request;

class OrderCollection extends BaseCollection
{
    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(function (Order $item) {
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
                    'expired_at' => $item->expired_at?->toDateTimeString(),
                    'paid_at' => $item->paid_at?->toDateTimeString(),
                    'signed_at' => $item->signed_at?->toDateTimeString(),
                    'created_at' => $item->created_at?->toDateTimeString(),
                ];
            }),
            'page' => $this->pagination(),
        ];
    }
}
