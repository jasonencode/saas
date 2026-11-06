<?php

namespace App\Models\Campaign;

use App\Enums\Campaign\SeckillSessionStatus;
use App\Models\Mall\Product;
use App\Models\Mall\Sku;
use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasEventLog;
use App\Policies\Campaign\SeckillSessionPolicy;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

/**
 * 秒杀场次
 *
 * 一个场次对应一个 SKU、一个时间窗、一个秒杀价与一份名额。
 *
 * @property SeckillSessionStatus $status
 * @property float $seckill_price
 */
#[Unguarded]
#[UsePolicy(SeckillSessionPolicy::class)]
class SeckillSession extends Model
{
    use BelongsToTenant,
        HasEventLog,
        SoftDeletes;

    protected function casts(): array
    {
        return [
            'seckill_price' => 'decimal:2',
            'stock' => 'integer',
            'sold' => 'integer',
            'limit_per_user' => 'integer',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'status' => SeckillSessionStatus::class,
            'sort' => 'integer',
        ];
    }

    /**
     * 保存前校验：价格、名额与同 SKU 时间窗
     */
    protected static function booted(): void
    {
        static::saving(function (SeckillSession $session): void {
            if ($session->start_at && $session->end_at && $session->end_at->lte($session->start_at)) {
                throw ValidationException::withMessages(['end_at' => '结束时间必须晚于开始时间']);
            }

            if ($session->sku_id === null || $session->start_at === null || $session->end_at === null) {
                return;
            }

            $sku = Sku::query()->with('product')->find($session->sku_id);

            if ($sku === null) {
                return;
            }

            if (bccomp((string) $session->seckill_price, (string) $sku->price, 2) !== -1) {
                throw ValidationException::withMessages(['seckill_price' => '秒杀价必须低于商品原价']);
            }

            if ($session->stock > $sku->stock) {
                throw ValidationException::withMessages(['stock' => '秒杀名额不能超过 SKU 当前库存']);
            }

            $overlap = static::query()
                ->where('sku_id', $session->sku_id)
                ->when($session->exists, fn ($query) => $query->whereKeyNot($session->getKey()))
                ->whereIn('status', [
                    SeckillSessionStatus::Scheduled->value,
                    SeckillSessionStatus::Running->value,
                ])
                ->where('start_at', '<', $session->end_at)
                ->where('end_at', '>', $session->start_at)
                ->exists();

            if ($overlap) {
                throw ValidationException::withMessages(['start_at' => '该 SKU 在所选时间段已有秒杀场次']);
            }
        });
    }

    /**
     * 所属活动
     *
     * @return BelongsTo<SeckillActivity>
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(SeckillActivity::class, 'activity_id');
    }

    /**
     * 参与秒杀的商品
     *
     * @return BelongsTo<Product>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * 参与秒杀的 SKU
     *
     * @return BelongsTo<Sku>
     */
    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
    }

    /**
     * 秒杀记录
     *
     * @return HasMany<SeckillRecord>
     */
    public function records(): HasMany
    {
        return $this->hasMany(SeckillRecord::class, 'session_id');
    }

    /**
     * 剩余名额
     */
    public function remaining(): int
    {
        return max(0, $this->stock - $this->sold);
    }

    /**
     * 剩余名额（表格访问器）
     */
    public function getRemainingAttribute(): int
    {
        return $this->remaining();
    }

    /**
     * 判断指定时间是否可购买（时间窗 + 状态 + 名额）
     */
    public function isPurchasable(?CarbonInterface $now = null): bool
    {
        $now ??= now();

        return $this->status->isPurchasable()
            && $now->betweenIncluded($this->start_at, $this->end_at)
            && $this->remaining() > 0;
    }
}
