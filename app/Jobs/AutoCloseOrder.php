<?php

namespace App\Jobs;

use App\Jobs\BaseJob;
use Exception;
use App\Models\Mall\Order;

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
