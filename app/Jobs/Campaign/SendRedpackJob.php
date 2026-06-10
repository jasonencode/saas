<?php

namespace App\Jobs\Campaign;

use App\Jobs\BaseJob;
use App\Models\Campaign\Redpack;
use App\Models\User\User;

/**
 * 红包发送任务
 *
 * 用于异步向指定用户发送红包，具体业务逻辑待实现。
 */
class SendRedpackJob extends BaseJob
{
    public int $timeout = 60;

    public int $tries = 3;

    public function __construct(
        protected Redpack $redpack,
        protected User $user,
        protected float $amount,
    ) {
    }

    public function handle(): void
    {
        // TODO: 实现红包发送逻辑
        // 1. 检查红包活动是否有效
        // 2. 检查用户是否满足领取条件
        // 3. 创建红包码并关联用户
        // 4. 发送通知
    }
}
