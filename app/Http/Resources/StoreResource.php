<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'store_id' => $this->id,
            'name' => $this->name,
            'cover' => $this->cover,
            'description' => $this->description,
            'is_self' => $this->is_self,
        ];
    }
}