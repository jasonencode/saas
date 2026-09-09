<?php

namespace App\Models\Finance;

use App\Enums\Finance\WithdrawGateway;
use App\Enums\Finance\WithdrawOrderStatus;
use App\Models\Model;
use App\Models\System\Administrator;
use App\Models\Traits\AutoCreateOrderNo;
use App\Models\Traits\BelongsToUser;
use App\Policies\Finance\WithdrawOrderPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Unguarded]
#[UsePolicy(WithdrawOrderPolicy::class)]
class WithdrawOrder extends Model
{
    use AutoCreateOrderNo,
        BelongsToUser,
        SoftDeletes;

    protected function casts(): array
    {
        return [
            'gateway' => WithdrawGateway::class,
            'status' => WithdrawOrderStatus::class,
            'amount' => 'decimal:2',
            'fee' => 'decimal:2',
            'actual_amount' => 'decimal:2',
            'account_info' => 'json',
            'reviewed_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        self::creating(static function (self $model) {
            $model->status = WithdrawOrderStatus::Pending;
        });
    }

    /**
     * 审核人
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Administrator::class, 'reviewer_id');
    }
}
