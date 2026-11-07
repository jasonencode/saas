<?php

namespace App\Console\Commands\Campaign;

use App\Enums\Campaign\RedpackCodeStatus;
use App\Models\Campaign\RedpackCode;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

#[Signature('redpack:expire')]
class RedpackExpireCommand extends Command
{
    protected $description = '禁用已结束或已禁用活动的待领取红包码';

    public function handle(): int
    {
        $count = RedpackCode::query()
            ->where('status', RedpackCodeStatus::Active->value)
            ->whereHas('redpack', function (Builder $builder): void {
                $builder->where('status', false)
                    ->orWhere(fn (Builder $builder) => $builder
                        ->whereNotNull('end_at')
                        ->where('end_at', '<', now()));
            })
            ->update(['status' => RedpackCodeStatus::Disabled->value]);

        $this->info(sprintf('已禁用 %d 个过期红包码', $count));

        return self::SUCCESS;
    }
}
