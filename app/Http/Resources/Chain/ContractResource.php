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
            'name' => $this->name,
            'address' => $this->address,
            'abi' => $this->abi,
            'bytecode' => $this->bytecode,
            'deploy_status' => $this->deploy_status,
            'type' => $this->type,
            'network' => new NetworkResource($this->whenLoaded('network')),
            'deployer' => new ChainAddressResource($this->whenLoaded('deployer')),
        ];
    }
}
