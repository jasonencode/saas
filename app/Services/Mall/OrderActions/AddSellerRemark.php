<?php

namespace App\Services\Mall\OrderActions;

use App\Contracts\Authenticatable;
use App\Contracts\Mall\AbstractOperation;
use App\Models\Mall\Order;
use App\Services\System\EventLogService;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * 添加商家备注
 *
 * 入口：OrderService::addSellerRemark() / AddSellerRemark::dispatch()。
 *
 * before：空
 * handle：写入商家备注
 * log   ：写日志中心 order.seller_remark_added
 * after ：空
 */
final class AddSellerRemark extends AbstractOperation
{
    /**
     * 修改前的备注（供日志记录）
     */
    private ?string $previousRemark = null;

    public function __construct(
        private readonly Order $order,
        private readonly string $remark,
        private readonly Authenticatable $operator,
    ) {}

    /**
     * 前置：空
     */
    public function before(): void {}

    /**
     * 写入商家备注
     *
     * @throws Throwable
     */
    public function handle(): bool
    {
        return DB::transaction(function (): bool {
            $this->previousRemark = $this->order->seller_remark;

            $this->order->seller_remark = $this->remark;
            $this->order->save();

            return true;
        });
    }

    /**
     * 记录商家备注日志
     *
     * @param  mixed  $result  handle() 的返回值
     */
    public function log(mixed $result): void
    {
        service(EventLogService::class)->write('order.seller_remark_added', '添加商家备注', $this->operator, $this->order, null, [
            'old' => $this->previousRemark,
            'new' => $this->remark,
        ]);
    }
}
