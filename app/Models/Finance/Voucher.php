<?php

namespace App\Models\Finance;

use App\Enums\Finance\VoucherStatus;
use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\BelongsToUser;
use App\Policies\Finance\VoucherPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

#[Unguarded]
#[UsePolicy(VoucherPolicy::class)]
class Voucher extends Model
{
    use BelongsToTenant,
        BelongsToUser,
        SoftDeletes;

    /**
     * 单号前缀
     *
     * @var string
     */
    const string NO_PREFIX = 'Sov-';

    /**
     * 序号位数
     *
     * @var int
     */
    const int NO_SERIAL_LENGTH = 6;

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'status' => VoucherStatus::class,
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        self::creating(static function (Voucher $voucher) {
            $voucher->status = VoucherStatus::Pending;
            $voucher->no = $voucher->generateNo();
        });
    }

    /**
     * 生成凭据单号
     *
     * 格式：`Sov-YYYYMMDDNNNNNN`，其中末段为当天递增序号（6 位，不足补零）。
     *
     * 并发安全依赖数据库 `no` 字段的唯一约束：并发创建若产生相同序号，
     * 后提交者会触发唯一约束冲突。上层调用方需自行处理该异常或使用重试。
     */
    protected function generateNo(): string
    {
        $today = Carbon::today();

        $lastNo = self::withTrashed()
            ->whereBetween('created_at', [$today, $today->copy()->endOfDay()])
            ->orderByDesc('id')
            ->value('no');

        $lastSerial = $lastNo ? (int) substr($lastNo, -self::NO_SERIAL_LENGTH) : 0;

        return self::NO_PREFIX.$today->format('Ymd').str_pad((string) ($lastSerial + 1), self::NO_SERIAL_LENGTH, '0', STR_PAD_LEFT);
    }

    /**
     * 关联计划
     *
     * @return BelongsTo<Plan>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * 关联目标模型
     *
     * @return MorphTo<Model>
     */
    public function target(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * 设置目标模型
     *
     * @param  Model  $model  目标模型
     */
    public function setTargetAttribute(Model $model): void
    {
        $this->attributes['target_type'] = $model->getMorphClass();
        $this->attributes['target_id'] = $model->getKey();
    }
}
