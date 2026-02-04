<?php

namespace App\Models;

use App\Models\Model;
use App\Models\Traits\AutoCreateOrderNo;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\BelongsToUser;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\OrderStatus;
use App\Events\OrderCreated;
use App\Models\Traits\OrderScopes;
use RuntimeException;

class Order extends Model
{
    use AutoCreateOrderNo,
        BelongsToTenant,
        BelongsToUser,
        OrderScopes,
        SoftDeletes;

    protected $table = 'mall_orders';

    protected string $orderNoPrefix = 'OR';

    protected $casts = [
        'status' => OrderStatus::class,
        'expired_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    protected $dispatchesEvents = [
        'created' => OrderCreated::class,
    ];

    protected static function boot(): void
    {
        parent::boot();

        self::creating(function(Order $order) {
            $order->expired_at = Carbon::now()->addMinutes((int) config('mall.order_expired_minutes'));
        });
    }

    public function getRouteKeyName(): string
    {
        return 'no';
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function expresses(): HasMany
    {
        return $this->hasMany(OrderExpress::class);
    }

    public function address(): hasOne
    {
        return $this->hasOne(OrderAddress::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(OrderLog::class);
    }

    public function getTotalAmountAttribute(): string
    {
        return bcadd($this->amount, $this->freight, 2);
    }

    public function paid(Carbon $carbon): bool
    {
        if ($this->status !== OrderStatus::Pending) {
            throw new RuntimeException('订单状态不可支付');
        }
        $this->status = OrderStatus::Paid;
        $this->paid_at = $carbon;

        return $this->save();
    }
}
