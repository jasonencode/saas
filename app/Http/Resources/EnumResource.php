<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 枚举序列化资源
 *
 * 将带标签的 backed enum 统一输出为 {value, label} 结构，
 * 供各模块 resource 复用，避免重复手写格式化数组。
 */
class EnumResource extends JsonResource
{
    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'value' => $this->resource->value,
            'label' => $this->resource->getLabel(),
        ];
    }
}