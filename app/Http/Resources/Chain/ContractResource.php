<?php

namespace App\Http\Resources\Chain;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractResource extends JsonResource
{
    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'contract_id' => $this->resource->id,
            'name' => $this->resource->name,
            'address' => $this->resource->address,
            'abi' => $this->resource->abi,
            'bytecode' => $this->resource->bytecode,
            'deploy_status' => $this->resource->deploy_status,
            'type' => $this->resource->type,
            'network' => NetworkResource::make($this->resource->network),
            'deployer' => ChainAddressResource::make($this->whenLoaded('deployer')),
        ];
    }
}
