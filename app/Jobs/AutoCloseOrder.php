<?php

namespace App\Jobs;

use App\Enums\Mall\OrderStatus;
use App\Models\Mall\Order;
use App\Services\OrderService;
use Exception;

/**
 * 自动关闭订单任务类
 */
class AutoCloseOrder extends BaseJob
{
    public function __construct(protected Order $order)
    {
    }

    public function handle(): void
    {
        try {
            if ($this->order->status !== OrderStatus::Pending) {
                return;
            }

            $orderService = app(OrderService::class);
            $orderService->cancel($this->order, $this->user());
        } catch (Exception $e) {

        }
    }
}
