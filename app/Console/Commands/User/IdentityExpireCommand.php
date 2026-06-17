<?php

namespace App\Console\Commands\User;

use App\Models\User\User;
use App\Services\User\IdentityService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Signature('app:user:identity-expire')]
#[Description('自动清理用户已过期的身份')]
class IdentityExpireCommand extends Command
{
    public function handle(IdentityService $service): int
    {
        $this->info('开始执行身份过期清理扫描...');

        $total = 0;

        User::whereExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('user_identity')
                ->whereColumn('user_identity.user_id', 'users.id')
                ->whereNotNull('user_identity.end_at')
                ->where('user_identity.end_at', '<=', now());
        })->chunkById(100, function ($users) use ($service, &$total) {
            foreach ($users as $user) {
                try {
                    $count = $service->removeExpiredForUser($user);
                    if ($count > 0) {
                        $total += $count;
                        $this->line("用户 [{$user->getKey()}] 已清理 {$count} 个过期身份");
                    }
                } catch (Throwable $e) {
                    $this->error("用户 [{$user->getKey()}] 身份清理失败: ".$e->getMessage());
                    Log::error("Identity Expire Error [user={$user->getKey()}]: ".$e->getMessage());
                }
            }
        });

        $this->info("任务执行完毕，共清理 {$total} 个过期身份。");

        return self::SUCCESS;
    }
}
