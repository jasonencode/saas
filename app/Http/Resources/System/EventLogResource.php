<?php

namespace App\Http\Resources\System;

use App\Models\System\EventLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 日志中心事件资源
 *
 * 将 event_logs 输出为 {event, action, payload, operator, created_at}，
 * 其中 action 保持 {value, label} 结构，value 为事件标识、label 为操作描述，
 * 兼容原订单/退款日志接口的展示方式。
 *
 * @mixin EventLog
 */
class EventLogResource extends JsonResource
{
    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'log_id' => $this->resource->id,
            'event' => $this->resource->event,
            'action' => [
                'value' => $this->resource->event,
                'label' => $this->resource->action,
            ],
            'description' => $this->resource->description,
            'payload' => $this->resource->payload,
            'context' => $this->resource->payload,
            'operator' => $this->resource->actor ? [
                'id' => $this->resource->actor_id,
                'type' => $this->resource->actor_type,
                'name' => $this->resource->actorName(),
            ] : null,
            'created_at' => $this->resource->created_at,
        ];
    }
}
