<?php

namespace App\Http\Resources\Users;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceTitleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'title_id' => $this->resource->getRouteKey(),
            'type' => $this->resource->type->value,
            'type_label' => $this->resource->type->getLabel(),
            'name' => $this->resource->title,
            'tax_no' => $this->resource->tax_no,
            'is_default' => $this->resource->is_default,
            'created_at' => (string) $this->resource->created_at,
        ];
    }
}
