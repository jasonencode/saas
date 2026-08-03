<?php

namespace App\Services\Mall\DTOs;

use App\Models\Mall\Order;
use App\Models\User\Address;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

class OrderResult implements Arrayable
{
    public function __construct(
        protected Collection $orders,
        protected Collection $items,
        protected ?Address $address,
    ) {}

    /**
     * @return Collection<Order>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    /**
     * 获取订单商品明细列表
     *
     * @return Collection<OrderItemDto>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    /**
     * 转换为数组
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'target' => app(Order::class)->getMorphClass(),
            'orders' => $this->orders->pluck('no'),
            'amount' => $this->getAmount(),
            'qty' => $this->getTotalQty(),
            'freight' => $this->getFreight(),
        ];
    }

    /**
     * 获取订单总金额
     *
     * @return string 金额（保留两位小数）
     */
    public function getAmount(): string
    {
        return number_format($this->orders->sum('amount'), 2, '.', '');
    }

    /**
     * 获取商品总数量
     *
     * @return int 数量
     */
    public function getTotalQty(): int
    {
        return $this->items->sum('qty');
    }

    /**
     * 获取总运费
     *
     * @return string 运费金额（保留两位小数）
     */
    public function getFreight(): string
    {
        return number_format($this->orders->sum('freight'), 2, '.', '');
    }
}
