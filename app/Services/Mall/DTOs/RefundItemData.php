<?php

namespace App\Services\Mall\DTOs;

use Illuminate\Contracts\Support\Arrayable;
use InvalidArgumentException;

/**
 * 退款商品明细 DTO
 *
 * 持有单个退款商品的标准化数据，构造时完成字段校验与类型归一。
 */
class RefundItemData implements Arrayable
{
    public function __construct(
        public int $orderItemId,
        public int $qty,
        public ?string $remark = null,
        public ?string $price = null,
    ) {
        if ($this->orderItemId <= 0) {
            throw new InvalidArgumentException('退款商品ID不合法');
        }

        if ($this->qty <= 0) {
            throw new InvalidArgumentException('退款数量必须为正整数');
        }

        if (mb_strlen($this->remark) > 200) {
            throw new InvalidArgumentException('退款商品备注不能超过200个字符');
        }

        if ($this->price !== null && (!is_numeric($this->price) || (float) $this->price < 0)) {
            throw new InvalidArgumentException('退款单价不合法');
        }
    }

    /**
     * 创建退款商品明细 DTO
     *
     * @param  array{order_item_id: int|string, qty: int|string, remark?: string, price?: string|int|float}  $data  原始商品数据
     */
    public static function make(array $data): self
    {
        return new self(
            orderItemId: (int) ($data['order_item_id'] ?? 0),
            qty: (int) ($data['qty'] ?? 0),
            remark: $data['remark'] ?? null,
            price: isset($data['price']) ? (string) $data['price'] : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'order_item_id' => $this->orderItemId,
            'qty' => $this->qty,
            'remark' => $this->remark,
            'price' => $this->price,
        ], static fn ($v) => $v !== null);
    }
}
