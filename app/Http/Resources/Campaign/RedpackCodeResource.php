<?php

namespace App\Http\Resources\Campaign;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RedpackCodeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->code,
            'amount' => (float) $this->amount,
            'status' => $this->status->value,
            'status_label' => $this->status->getLabel(),
            'claimed_at' => $this->claimed_at?->toDateTimeString(),
            'redpack' => new RedpackResource($this->whenLoaded('redpack')),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
