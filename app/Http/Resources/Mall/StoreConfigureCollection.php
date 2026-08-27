<?php

namespace App\Http\Resources\Mall;

use App\Http\Resources\BaseCollection;
use Illuminate\Http\Request;

class StoreConfigureCollection extends BaseCollection
{
    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'list' => $this->collection->map(fn ($item) => [
                'tenant_id' => $item->tenant_id,
                'store_name' => $item->store_name,
                'description' => $item->store_description,
                'logo' => $item->cover_url,
                'phone' => $item->phone,
                'contactor' => $item->contactor,
                'address' => $item->full_address,
            ]),
            'page' => $this->pagination(),
        ];
    }
}
