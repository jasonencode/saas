<?php

namespace App\Http\Resources\Campaign;

use App\Enums\Campaign\SeckillRecordStatus;
use App\Http\Resources\EnumResource;
use App\Models\Campaign\SeckillSession;
use App\Services\Mall\Seckill\RedisSeckillStockCounter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 秒杀场次
 *
 * @mixin SeckillSession
 */
class SeckillSessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $serverTime = now();

        $data = [
            'session_id' => $this->resource->getKey(),
            'activity_id' => $this->resource->activity_id,
            'name' => $this->resource->name,
            'tenant_id' => $this->resource->tenant_id,
            'product_id' => $this->resource->product?->getKey(),
            'product' => [
                'name' => $this->resource->product?->name,
                'cover' => $this->resource->product?->cover_url,
            ],
            'sku_id' => $this->resource->sku?->getKey(),
            'sku' => [
                'name' => $this->resource->sku?->name,
                'price' => $this->resource->sku?->price,
            ],
            'seckill_price' => $this->resource->seckill_price,
            'stock' => $this->resource->stock,
            'sold' => $this->resource->sold,
            'remaining' => service(RedisSeckillStockCounter::class)->remaining($this->resource),
            'limit_per_user' => $this->resource->limit_per_user,
            'start_at' => $this->resource->start_at?->toDateTimeString(),
            'end_at' => $this->resource->end_at?->toDateTimeString(),
            'status' => EnumResource::make($this->resource->status),
            'server_time' => $serverTime->toDateTimeString(),
            'countdown_seconds' => max(0, $this->resource->start_at?->getTimestamp() - $serverTime->getTimestamp()),
        ];

        if (($user = $request->user()) !== null) {
            $data['user_limit_remaining'] = max(
                0,
                $this->resource->limit_per_user - $this->usedQty($user->getKey()),
            );
        }

        return $data;
    }

    /**
     * 用户在当前场次已占用数量
     */
    private function usedQty(int $userId): int
    {
        return (int) $this->resource->records()
            ->where('user_id', $userId)
            ->whereIn('status', [
                SeckillRecordStatus::Reserved->value,
                SeckillRecordStatus::Ordered->value,
            ])
            ->sum('qty');
    }
}
