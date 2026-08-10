<?php

namespace App\Console\Commands\User;

use App\Models\User\User;
use App\Services\User\IdentityService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

#[Signature('app:user:identity-expire')]
#[Description('自动清理用户已过期的身份')]
class IdentityExpireCommand extends Command
{
    public function handle(IdentityService $service): int
    {
        $this->info('开始执行身份过期清理扫描...');

        $total = 0;

        User::whereHas('identities', static function (Builder $query) {
            $query->whereNotNull('user_identity.end_at')
                ->where('user_identity.end_at', '<=', now());
        })->chunkById(100, function (Collection $users) use ($service, &$total) {
            foreach ($users as $user) {
                try {
                    $count = $service->removeExpiredForUser($user);
                    if ($count > 0) {
                        $total += $count;
                        $this->line("用户 [{$user->getKey()}] 已清理 $count 个过期身份");
                    }
                } catch (Throwable $e) {
                    $this->error("用户 [{$user->getKey()}] 身份清理失败: ".$e->getMessage());
                }
            }
        });

        $this->info("任务执行完毕，共清理 $total 个过期身份。");

        return self::SUCCESS;
    }
}
