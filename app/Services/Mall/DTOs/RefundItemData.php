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
        public ?float $price = null,
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

        if ($this->price !== null && (!is_numeric($this->price) || $this->price < 0)) {
            throw new InvalidArgumentException('退款单价不合法');
        }
    }

    /**
     * 创建退款商品明细 DTO
     *
     * 参数与构造函数保持一致；字段校验由构造函数完成。
     */
    public static function make(
        int $orderItemId,
        int $qty,
        ?string $remark = null,
        ?float $price = null,
    ): self {
        return new self(
            orderItemId: $orderItemId,
            qty: $qty,
            remark: $remark,
            price: $price,
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
