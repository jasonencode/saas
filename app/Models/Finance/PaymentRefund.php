<?php

namespace App\Models\Finance;

use App\Enums\Finance\PaymentRefundStatus;
use App\Models\Model;
use App\Models\System\Administrator;
use App\Models\Traits\AutoCreateOrderNo;
use App\Models\Traits\BelongsToTenant;
use App\Models\User\User;
use App\Policies\Finance\PaymentRefundPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Unguarded]
#[UsePolicy(PaymentRefundPolicy::class)]
class PaymentRefund extends Model
{
    use AutoCreateOrderNo,
        BelongsToTenant,
        SoftDeletes;

    protected function casts(): array
    {
        return [
            'refunded_at' => 'datetime',
            'approved_at' => 'datetime',
            'amount' => 'decimal:2',
            'status' => PaymentRefundStatus::class,
        ];
    }

    /**
     * 关联支付单
     *
     * @return BelongsTo<PaymentOrder>
     */
    public function paymentOrder(): BelongsTo
    {
        return $this->belongsTo(PaymentOrder::class);
    }

    /**
     * 创建者（多态关联）
     *
     * @return MorphTo<Administrator|User>
     */
    public function creator(): MorphTo
    {
        return $this->morphTo('created_by');
    }

    /**
     * 来源单据（多态关联，如商城售后单）
     *
     * @return MorphTo<Model>
     */
    public function source(): MorphTo
    {
        return $this->morphTo('source');
    }

    /**
     * 设置来源单据
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model  来源模型
     */
    public function setSourceAttribute(\Illuminate\Database\Eloquent\Model $model): void
    {
        $this->attributes['source_type'] = $model->getMorphClass();
        $this->attributes['source_id'] = $model->getKey();
    }

    /**
     * 设置创建者
     *
     * 参数类型用框架基类：创建者可能是后台管理员或前台用户，两者不一定继承 App\Models\Model。
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model  创建者模型
     */
    public function setCreatorAttribute(\Illuminate\Database\Eloquent\Model $model): void
    {
        $this->attributes['created_by_type'] = $model->getMorphClass();
        $this->attributes['created_by_id'] = $model->getKey();
    }

    /**
     * 审批人
     *
     * @return BelongsTo<Administrator>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(Administrator::class, 'approved_by');
    }
}
