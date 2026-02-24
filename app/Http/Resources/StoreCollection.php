<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class StoreCollection extends BaseCollection
{
    public function toArray(Request $request): array
    {
        return [
            'list' => $this->collection,
            'page' => $this->pagination(),
        ];
    }
}