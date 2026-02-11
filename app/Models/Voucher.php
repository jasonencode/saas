<?php

namespace App\Models;

use App\Enums\VoucherStatus;
use App\Jobs\VoucherAutoRunJob;
use App\Models\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Voucher extends Model
{
    use BelongsToUser,
        SoftDeletes;

    protected $casts = [
        'completed_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'status' => VoucherStatus::class,
    ];

    protected static function boot(): void
    {
        parent::boot();

        self::creating(static function (Voucher $voucher) {
            $voucher->status = VoucherStatus::Pending;
            $count = Voucher::withTrashed()->whereDate('created_at', Carbon::today())->count() + 1;
            $no = 'Sov-'.date('Ymd').sprintf('%06d', $count);
            $voucher->no = $no;
        });

        self::created(static function (Voucher $voucher) {
            if ($voucher->scheduled_at && $voucher->scheduled_at->isFuture()) {
                VoucherAutoRunJob::dispatch($voucher)->delay($voucher->scheduled_at);
            } else {
                VoucherAutoRunJob::dispatch($voucher);
            }
        });
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function target(): MorphTo
    {
        return $this->morphTo();
    }

    public function setTargetAttribute(Model $model): void
    {
        $this->attributes['target_type'] = $model->getMorphClass();
        $this->attributes['target_id'] = $model->getKey();
    }
}
