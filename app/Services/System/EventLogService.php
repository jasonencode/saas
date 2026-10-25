<?php

namespace App\Services\System;

use App\Contracts\ServiceInterface;
use App\Models\System\EventLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * 日志中心写入
 *
 * 统一入口：谁（actor）在什么时间（created_at 由数据库写入）对什么对象（subject）做了什么（action）。
 *
 * 用法：
 *   service(EventLogService::class)->write('order.paid', '订单支付', $user, $order, payload: ['paid_at' => ...]);
 *
 * 写入失败不抛错：日志是旁路，落库异常只上报（report），返回 null，不影响业务主流程。
 */
class EventLogService implements ServiceInterface
{
    /**
     * User-Agent 最大长度，与 event_logs.user_agent 列宽一致
     */
    public const int USER_AGENT_MAX_LENGTH = 512;

    /**
     * 写入一条事件日志
     *
     * @param  string  $event  事件标识（点号分层，如 order.paid）
     * @param  string  $action  操作描述（如 订单支付）
     * @param  Model|null  $actor  操作者，null 表示系统行为（定时任务、回调等）
     * @param  Model|null  $subject  业务对象，null 表示不关联对象
     * @param  string|null  $description  补充说明
     * @param  array<string, mixed>  $payload  上下文/变更内容
     *
     * @return EventLog|null 写入成功返回记录，失败返回 null
     */
    public function write(
        string $event,
        string $action,
        ?Model $actor = null,
        ?Model $subject = null,
        ?string $description = null,
        array $payload = [],
    ): ?EventLog {
        $request = app()->runningInConsole() ? null : request();

        try {
            return EventLog::create([
                'event' => $event,
                'actor_type' => $actor?->getMorphClass(),
                'actor_id' => $actor?->getKey(),
                'subject_type' => $subject ? $subject::class : null,
                'subject_id' => $subject?->getKey(),
                'action' => $action,
                'description' => $description,
                'payload' => $payload ?: null,
                'ip' => $request?->ip(),
                'user_agent' => $this->userAgent($request?->userAgent()),
                'request_id' => $this->requestId(),
            ]);
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * 当前请求的链路追踪 ID
     *
     * AddDebugInfoMiddleware 优先沿用 X-Request-Id；客户端没传时它生成的 ID 只写入日志上下文
     * 与响应头，因此这里回落到 Log 共享上下文。
     */
    protected function requestId(): ?string
    {
        if (app()->runningInConsole()) {
            return null;
        }

        return request()->header('X-Request-Id') ?: (Log::sharedContext()['request_id'] ?? null);
    }

    /**
     * 截断超长 User-Agent
     *
     * 列宽 512，严格模式下超长会直接抛错，日志写入不能因此失败。
     */
    protected function userAgent(?string $userAgent): ?string
    {
        if ($userAgent === null) {
            return null;
        }

        return Str::limit($userAgent, self::USER_AGENT_MAX_LENGTH, '');
    }
}
