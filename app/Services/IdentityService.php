<?php

namespace App\Services;

use App\Contracts\ServiceInterface;
use App\Enums\IdentityChannel;
use App\Models\Identity;
use App\Models\IdentityLog;
use App\Models\User;
use App\Models\UserIdentity;
use Illuminate\Support\Carbon;

class IdentityService implements ServiceInterface
{
    /**
     * 用户添加身份
     */
    public function entry(
        User $user,
        Identity $identity,
        IdentityChannel $channel = IdentityChannel::Auto,
        int $qty = 1,
        array $source = []
    ): void {
        $pivot = UserIdentity::where('user_id', $user->getKey())->where('identity_id', $identity->getKey())->first();

        $data['end_at'] = match (true) {
            $pivot && $identity->days => $this->parseEndedAtTime(Carbon::parse($pivot->end_at)->addDays($identity->days * $qty)),
            ! $pivot && $identity->days => $this->parseEndedAtTime(Carbon::now()->addDays($identity->days * $qty)),
            default => null
        };

        $data['serial'] = $pivot ? $pivot->serial : UserIdentity::getNewestSerialNo($identity);
        ! $pivot && $data['start_at'] = now();

        $before = null;
        if (! config('custom.identity.allow_multiple')) {
            $before = $user->identities()->first();
            $user->identities()->syncWithPivotValues([$identity->getKey()], $data);
        } elseif ($pivot) {
            $user->identities()->updateExistingPivot($identity->getKey(), $data);
        } else {
            $user->identities()->attach($identity->getKey(), $data);
        }
        $this->generateIdentityLog($user, $before, $identity, $channel, $source);
    }

    /**
     * 用户移除身份
     */
    public function remove(
        User $user,
        Identity $identity,
        IdentityChannel $channel = IdentityChannel::Auto,
        array $source = []
    ): void {
        $pivot = UserIdentity::where('user_id', $user->getKey())
            ->where('identity_id', $identity->getKey())
            ->first();

        if (! $pivot) {
            return;
        }

        $user->identities()->detach($identity->getKey());
        $this->generateIdentityLog($user, $identity, null, $channel, $source);
    }

    /**
     * 移除用户过期的身份
     */
    public function removeExpiredForUser(
        User $user,
        IdentityChannel $channel = IdentityChannel::Auto
    ): int {
        $expired = $user->identities()
            ->wherePivotNotNull('end_at')
            ->wherePivot('end_at', '<=', now())
            ->get();

        $count = 0;
        foreach ($expired as $item) {
            $user->identities()->detach($item->getKey());
            $this->generateIdentityLog($user, $item, null, $channel, ['reason' => 'expired']);
            $count++;
        }

        return $count;
    }

    /**
     * 解析结束时间
     */
    private function parseEndedAtTime(Carbon $endedAT): Carbon
    {
        $maxDate = Carbon::create(9999, 12, 31, 23, 59, 59);
        if ($endedAT->greaterThan($maxDate)) {
            return $maxDate;
        }

        return $endedAT;
    }

    /**
     * 生成身份变更日志
     */
    private function generateIdentityLog(
        User $user,
        ?Identity $before,
        ?Identity $after,
        IdentityChannel $channel = IdentityChannel::Auto,
        array $source = []
    ): void {
        IdentityLog::create([
            'user' => $user,
            'before' => $before?->getKey() ?? 0,
            'after' => $after?->getKey() ?? 0,
            'channel' => $channel,
            'source' => $source,
        ]);
    }
}
