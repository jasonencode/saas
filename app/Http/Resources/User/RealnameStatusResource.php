<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 实名认证状态资源
 *
 * 将 UserRealname 模型（或 null）统一输出为 {value, label, color} 结构。
 * 有记录时取其 status 枚举；无记录（未提交）时输出占位状态。
 */
class RealnameStatusResource extends JsonResource
{
    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        $status = $this->resource?->status;

        return [
            'value' => $status?->value,
            'label' => $status?->getLabel() ?? '未提交',
            'color' => $status?->getColor() ?? 'gray',
        ];
    }
}
