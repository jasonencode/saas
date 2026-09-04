<?php

namespace App\Http\Resources\Chain;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CertificateResource extends JsonResource
{
    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'certificate_id' => $this->resource->id,
            'common_name' => $this->resource->common_name,
            'type' => $this->resource->type,
            'sign_type' => $this->resource->sign_type,
            'status' => $this->resource->status,
            'days' => $this->resource->days,
            'dn' => $this->resource->dn,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
