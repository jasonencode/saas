<?php

namespace App\Jobs;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderService;
use Exception;

/**
 * 自动签收订单任务类
 */
class AutoSignOrder extends BaseJob
{
    public function __construct(protected Order $order)
    {
    }

    public function handle(): void
    {
        try {
            if ($this->order->status !== OrderStatus::Delivered) {
                return;
            }

            $orderService = app(OrderService::class);
            $orderService->sign($this->order, $this->user());
        } catch (Exception $e) {
            report($e);
        }
    }
}
