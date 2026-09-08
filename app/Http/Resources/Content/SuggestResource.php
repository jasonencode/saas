<?php

namespace App\Http\Resources\Content;

use App\Http\Resources\EnumResource;
use App\Models\Content\Suggest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Suggest $resource
 */
class SuggestResource extends JsonResource
{
    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'suggest_id' => $this->resource->id,
            'type' => EnumResource::make($this->resource->type),
            'contact' => $this->resource->contact,
            'status' => EnumResource::make($this->resource->status),
            'last_message_at' => $this->resource->last_message_at,
            'created_at' => $this->resource->created_at,
        ];
    }
}
