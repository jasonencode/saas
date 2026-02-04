<?php

namespace App\Services\DTO;

use App\Models\Address;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use App\Models\Order;

class OrderResult implements Arrayable
{
    public function __construct(
        protected Collection $orders,
        protected Collection $items,
        protected ?Address $address,
    ) {
    }

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

    public function getItems(): Collection
    {
        return $this->items;
    }

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

    public function getAmount(): string
    {
        return number_format($this->orders->sum('amount'), 2, '.', '');
    }

    public function getTotalQty(): int
    {
        return $this->items->sum('qty');
    }

    public function getFreight(): string
    {
        return number_format($this->orders->sum('freight'), 2, '.', '');
    }
}