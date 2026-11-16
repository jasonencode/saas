<?php

namespace App\Models\Finance;

use App\Enums\Finance\PlanTrigger;
use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasEasyStatus;
use App\Models\Traits\HasSortable;
use App\Policies\Finance\PlanPolicy;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use RuntimeException;

#[Unguarded]
#[UsePolicy(PlanPolicy::class)]
class Plan extends Model
{
    use BelongsToTenant,
        Cachable,
        HasEasyStatus,
        HasSortable,
        SoftDeletes;

    protected function casts(): array
    {
        return [
            'trigger' => PlanTrigger::class,
        ];
    }

    protected static function booted(): void
    {
        // 凭据引用计划做结算回放，永久删除会留下无法追溯的孤儿凭据
        static::forceDeleting(static function (Plan $plan): void {
            if ($plan->vouchers()->withTrashed()->exists()) {
                throw new RuntimeException('该计划已产生结算凭据，禁止永久删除');
            }
        });
    }

    /**
     * 关联结算凭据
     *
     * @return HasMany<Voucher>
     */
    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    /**
     * 关联任务
     *
     * @return HasMany<Task>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
