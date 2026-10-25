<?php

namespace App\Models\System;

use App\Models\Model;
use App\Models\User\User;
use App\Policies\System\EventLogPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * 日志中心
 *
 * 通用事件日志：谁（actor）在什么时间（created_at）对什么对象（subject）做了什么（action）。
 *
 * @property string $event
 * @property string $action
 * @property string|null $description
 * @property array<string, mixed>|null $payload
 * @property string|null $ip
 * @property string|null $user_agent
 * @property string|null $request_id
 * @property Carbon $created_at
 */
#[Unguarded]
#[UsePolicy(EventLogPolicy::class)]
class EventLog extends Model
{
    use Prunable;

    const null UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    /**
     * 操作者
     *
     * @return MorphTo<Model>
     */
    public function actor(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * 业务对象
     *
     * @return MorphTo<Model>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * 操作者显示名
     *
     * 用户取昵称/用户名，管理员与系统账号取名称。
     */
    public function actorName(): ?string
    {
        return $this->actor?->name ?? $this->actor?->username;
    }

    /**
     * 操作者类型名
     */
    public function actorTypeName(): ?string
    {
        return match ($this->actor_type) {
            User::class => '用户',
            Administrator::class => '管理员',
            System::class => '系统',
            null => null,
            default => $this->actor_type,
        };
    }

    /**
     * 获取可修剪的模型查询
     *
     * @return Builder<EventLog>
     */
    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subDays(180));
    }
}
