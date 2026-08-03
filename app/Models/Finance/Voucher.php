<?php

namespace App\Models\Finance;

use App\Enums\Finance\VoucherStatus;
use App\Jobs\Finance\VoucherAutoRunJob;
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
use Illuminate\Support\Facades\DB;

#[Unguarded]
#[UsePolicy(VoucherPolicy::class)]
class Voucher extends Model
{
    use BelongsToTenant,
        BelongsToUser,
        SoftDeletes;

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
            $voucher->no = DB::transaction(static function () {
                $lastNo = Voucher::withTrashed()
                    ->whereDate('created_at', Carbon::today())
                    ->lockForUpdate()
                    ->orderByDesc('id')
                    ->value('no');

                if ($lastNo) {
                    $lastSerial = (int) substr($lastNo, -6);
                } else {
                    $lastSerial = 0;
                }

                return 'Sov-'.date('Ymd').sprintf('%06d', $lastSerial + 1);
            });
        });

        self::created(static function (Voucher $voucher) {
            if ($voucher->scheduled_at && $voucher->scheduled_at->isFuture()) {
                VoucherAutoRunJob::dispatch($voucher)->delay($voucher->scheduled_at);
            } else {
                VoucherAutoRunJob::dispatch($voucher);
            }
        });
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
