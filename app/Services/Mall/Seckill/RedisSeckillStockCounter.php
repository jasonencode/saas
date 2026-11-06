<?php

namespace App\Services\Mall\Seckill;

use App\Contracts\ServiceInterface;
use App\Enums\Campaign\SeckillRecordStatus;
use App\Enums\Campaign\SeckillReserveFailure;
use App\Models\Campaign\SeckillRecord;
use App\Models\Campaign\SeckillSession;
use App\Models\User\User;
use App\Services\Mall\Seckill\Concerns\ChecksSeckillAvailability;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * 名额计数器：Redis + Lua 原子扣减
 */
class RedisSeckillStockCounter implements ServiceInterface
{
    use ChecksSeckillAvailability;

    /**
     * 扣减名额与用户额度
     *
     * 返回：0=成功，1=名额不足，2=超出限购，-1=未预热
     */
    private const string RESERVE_SCRIPT = <<<'LUA'
        local stock = tonumber(redis.call('GET', KEYS[1])) or -1
        if stock < 0 then return -1 end
        if stock < tonumber(ARGV[1]) then return 1 end
        local used = tonumber(redis.call('GET', KEYS[2])) or 0
        if used + tonumber(ARGV[1]) > tonumber(ARGV[2]) then return 2 end
        redis.call('DECRBY', KEYS[1], ARGV[1])
        redis.call('INCRBY', KEYS[2], ARGV[1])
        return 0
    LUA;

    /**
     * 回补名额与用户额度
     */
    private const string RELEASE_SCRIPT = <<<'LUA'
        redis.call('INCRBY', KEYS[1], ARGV[1])
        local used = tonumber(redis.call('GET', KEYS[2])) or 0
        local next = used - tonumber(ARGV[1])
        if next > 0 then
            redis.call('SET', KEYS[2], next, 'KEEPTTL')
        else
            redis.call('DEL', KEYS[2])
        end
        return 1
    LUA;

    /**
     * 场次开始前预热名额与状态
     */
    public function prepare(SeckillSession $session): void
    {
        $ttl = $this->ttl($session);

        Redis::setex($this->stockKey($session), $ttl, max(0, $session->remaining()));
        Redis::setex($this->stateKey($session), $ttl, $session->status->value);
    }

    /**
     * Lua 原子扣减后落 reserved 记录；落库失败回滚名额
     */
    public function reserve(SeckillSession $session, User $user, int $qty): SeckillReserveResult
    {
        $session = SeckillSession::query()->find($session->getKey());

        if ($session === null) {
            return SeckillReserveResult::fail(SeckillReserveFailure::NotReady);
        }

        if ($failure = $this->availabilityFailure($session)) {
            return SeckillReserveResult::fail($failure);
        }

        $stockKey = $this->stockKey($session);
        $userKey = $this->userKey($session, $user);

        $code = (int) Redis::eval(self::RESERVE_SCRIPT, 2, $stockKey, $userKey, $qty, $session->limit_per_user);

        if ($code !== 0) {
            return SeckillReserveResult::fail(match ($code) {
                1 => SeckillReserveFailure::SoldOut,
                2 => SeckillReserveFailure::OverLimit,
                default => SeckillReserveFailure::NotReady,
            });
        }

        Redis::expire($userKey, $this->ttl($session));

        try {
            $record = DB::transaction(function () use ($session, $user, $qty): SeckillRecord {
                $record = SeckillRecord::create([
                    'tenant_id' => $session->tenant_id,
                    'activity_id' => $session->activity_id,
                    'session_id' => $session->getKey(),
                    'user_id' => $user->getKey(),
                    'qty' => $qty,
                    'price' => $session->seckill_price,
                    'status' => SeckillRecordStatus::Reserved,
                ]);

                // 数据库 sold 仅做展示与对账，真实扣减以 Redis 为准
                SeckillSession::query()->whereKey($session->getKey())->increment('sold', $qty);

                return $record;
            });
        } catch (Throwable $exception) {
            report($exception);

            $this->rollbackRedis($session, $user, $qty);

            return SeckillReserveResult::fail(SeckillReserveFailure::NotReady);
        }

        return SeckillReserveResult::ok($record);
    }

    /**
     * 回补名额、落库状态并同步 sold
     */
    public function release(SeckillRecord $record, SeckillRecordStatus $status): void
    {
        DB::transaction(function () use ($record, $status): void {
            $fresh = SeckillRecord::query()
                ->whereKey($record->getKey())
                ->lockForUpdate()
                ->first();

            if ($fresh === null || $fresh->status->isFinal()) {
                return;
            }

            $qty = $fresh->qty;
            $session = SeckillSession::query()->find($fresh->session_id);
            $user = $fresh->user;

            $fresh->update(['status' => $status]);

            SeckillSession::query()
                ->whereKey($fresh->session_id)
                ->where('sold', '>=', $qty)
                ->decrement('sold', $qty);

            if ($session !== null && $user !== null) {
                try {
                    Redis::eval(
                        self::RELEASE_SCRIPT,
                        2,
                        $this->stockKey($session),
                        $this->userKey($session, $user),
                        $qty,
                    );
                } catch (Throwable $exception) {
                    // Redis 不可用时以数据库 sold 为准，由对账任务修复
                    report($exception);
                }
            }
        });
    }

    /**
     * 剩余名额，Redis 缺失时回退数据库
     */
    public function remaining(SeckillSession $session): int
    {
        $value = Redis::get($this->stockKey($session));

        return $value === null ? $session->remaining() : max(0, (int) $value);
    }

    /**
     * 回滚 Redis 扣减（落库失败时）
     */
    private function rollbackRedis(SeckillSession $session, User $user, int $qty): void
    {
        try {
            Redis::eval(
                self::RELEASE_SCRIPT,
                2,
                $this->stockKey($session),
                $this->userKey($session, $user),
                $qty,
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function stockKey(SeckillSession $session): string
    {
        return $this->prefix($session).':stock';
    }

    private function stateKey(SeckillSession $session): string
    {
        return $this->prefix($session).':state';
    }

    private function userKey(SeckillSession $session, User $user): string
    {
        return $this->prefix($session).':user:'.$user->getKey();
    }

    private function prefix(SeckillSession $session): string
    {
        return sprintf('%s:%s:session:%s', config('seckill.redis_prefix'), $session->tenant_id, $session->getKey());
    }

    /**
     * key 过期时间：场次结束后再保留一段时间用于对账
     */
    private function ttl(SeckillSession $session): int
    {
        $expiresAt = $session->end_at->copy()->addHours((int) config('seckill.redis_ttl_hours', 24));

        return max(3600, now()->diffInSeconds($expiresAt, false));
    }
}
