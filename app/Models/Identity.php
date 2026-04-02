<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasCovers;
use App\Models\Traits\HasEasyStatus;
use App\Models\Traits\HasSortable;
use App\Policies\IdentityPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 身份模型
 */
#[Unguarded]
#[UsePolicy(IdentityPolicy::class)]
class Identity extends Model
{
    use BelongsToTenant,
        HasCovers,
        HasEasyStatus,
        HasSortable,
        SoftDeletes;

    protected $casts = [
        'price' => 'decimal:2',
        'is_default' => 'bool',
        'is_unique' => 'bool',
        'can_subscribe' => 'bool',
        'serial_open' => 'bool',
        'conditions' => 'json',
        'rules' => 'json',
    ];

    protected static function boot(): void
    {
        parent::boot();

        self::saved(static function ($model) {
            if ($model->is_default) {
                Identity::where('tenant_id', $model->tenant_id)
                    ->where('id', '<>', $model->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
    }

    /**
     * 当前身份对应的用户
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_identity')
            ->withPivot(['start_at', 'end_at', 'serial'])
            ->using(UserIdentity::class)
            ->withTimestamps();
    }

    /**
     * 关联订单
     */
    public function orders(): HasMany
    {
        return $this->hasMany(IdentityOrder::class);
    }

    /**
     * 身份变更日志（作为变更前身份）
     */
    public function beforeLogs(): HasMany
    {
        return $this->hasMany(IdentityLog::class, 'before_id');
    }

    /**
     * 身份变更日志（作为变更后身份）
     */
    public function afterLogs(): HasMany
    {
        return $this->hasMany(IdentityLog::class, 'after_id');
    }
}
