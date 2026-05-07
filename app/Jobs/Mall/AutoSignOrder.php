<?php

namespace App\Jobs\Mall;

use App\Enums\Mall\OrderStatus;
use App\Jobs\BaseJob;
use App\Models\Mall\Order;
use App\Services\Mall\OrderService;
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

            $orderService = service(OrderService::class);
            $orderService->sign($this->order, $this->user());
        } catch (Exception $e) {
            report($e);
        }
    }
}
