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
            'common_name' => $this->common_name,
            'type' => $this->type,
            'sign_type' => $this->sign_type,
            'status' => $this->status,
            'days' => $this->days,
            'dn' => $this->dn,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
