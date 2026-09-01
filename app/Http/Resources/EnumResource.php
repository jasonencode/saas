<?php

namespace App\Http\Resources;

use Filament\Support\Contracts\HasColor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 枚举序列化资源
 *
 * 将带标签的 backed enum 统一输出为 {value, label} 结构，
 * 若枚举实现了 HasColor（即具备 getColor），则额外输出 color。
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
            'color' => $this->when(
                $this->resource instanceof HasColor,
                fn () => $this->resource->getColor(),
            ),
        ];
    }
}
