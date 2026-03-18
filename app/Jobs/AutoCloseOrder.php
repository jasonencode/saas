<?php

namespace App\Jobs;

use App\Models\Order;
use Exception;

/**
 * 自动关闭订单任务类
 *
 * @module 商城
 */
class AutoCloseOrder extends BaseJob
{
    public function __construct(protected Order $order)
    {
    }

    public function handle(): void
    {
        try {
            $this->order->cancel();
            $this->order->logs()->create([
                'user' => $this->user(),
                'context' => [
                    'action' => 'AutoClose',
                    'message' => '订单自动关闭',
                ],
            ]);
            $this->order->expired_at = null;
            $this->order->save();
        } catch (Exception) {
        }
    }
}
