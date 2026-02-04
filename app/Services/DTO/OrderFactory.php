<?php

namespace App\Factories\Order;

use App\Models\Address;
use App\Models\User;
use Exception;
use Illuminate\Support\Collection;
use App\Services\OrderService;

class OrderFactory
{
    protected Collection $items;

    protected ?Address $address = null;

    public function __construct(protected User $user)
    {
    }

    public static function make(User $user): static
    {
        return new self($user);
    }

    /**
     * @param  array<OrderItem>  $items
     * @return $this
     * @throws Exception
     */
    public function setItems(array $items): static
    {
        if (blank($items)) {
            throw new Exception('订单无商品');
        }

        foreach ($items as $item) {
            if (!($item instanceof OrderItem)) {
                throw new Exception('商品必须实现 OrderItem 类');
            }
        }
        $this->items = new Collection($items);

        return $this;
    }

    /**
     * @throws Exception
     */
    public function setAddress(Address|int|null $address = null): static
    {
        if (empty($address)) {
            return $this;
        }
        if ($address instanceof Address) {
            $this->address = $address;
        } elseif (is_numeric($address)) {
            $address = Address::find($address);
            if (!$address) {
                throw new Exception('地址不正确');
            }
            if ($address->user->isNot($this->user)) {
                throw new Exception('地址不正确');
            }
            $this->address = $address;
        }

        return $this;
    }

    final public function create(): OrderResult
    {
        return app(OrderService::class)->createOrders(
            user: $this->user,
            items: $this->items?->all() ?? [],
            address: $this->address,
        );
    }
}
