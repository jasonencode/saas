<?php

namespace App\Services\Mall\DTOs;

use App\Enums\Mall\RefundReason;
use App\Enums\Mall\RefundType;
use Illuminate\Contracts\Support\Arrayable;
use InvalidArgumentException;

/**
 * 退款申请数据 DTO
 *
 * 构造时完成字段校验（原因匹配/原因详情/退运费/商品列表）。
 */
class RefundData implements Arrayable
{
    /**
     * @param  RefundType  $type  退款类型
     * @param  RefundReason  $reason  退款原因
     * @param  string|null  $reasonDetail  原因详情
     * @param  RefundItemData[]  $items  退款商品列表（一维数组，元素为 RefundItemData）
     * @param  float  $freightAmount  退运费金额
     */
    public function __construct(
        public RefundType $type,
        public RefundReason $reason,
        public ?string $reasonDetail,
        public array $items,
        public float $freightAmount = 0,
    ) {
        if (!array_key_exists($reason->value, $type->reasons())) {
            throw new InvalidArgumentException('该退款类型不支持此退款原因');
        }

        if ($reason === RefundReason::Other && (!is_string($reasonDetail) || trim($reasonDetail) === '')) {
            throw new InvalidArgumentException('选择"其他"原因时必须填写原因详情');
        }

        if ($reasonDetail !== null && mb_strlen($reasonDetail) > 200) {
            throw new InvalidArgumentException('退款原因详情不能超过200个字符');
        }

        if (!is_numeric($freightAmount) || $freightAmount < 0) {
            throw new InvalidArgumentException('退运费金额不合法');
        }

        foreach ($items as $item) {
            if (!$item instanceof RefundItemData) {
                throw new InvalidArgumentException('items 必须是由 RefundItemData 组成的一维数组');
            }
        }
    }

    /**
     * 创建退款数据 DTO
     *
     * 参数与构造函数保持一致；枚举归一与业务校验由调用方 / 服务层负责。
     *
     * @param  RefundItemData[]  $items  退款商品列表（一维数组，元素为 RefundItemData）
     */
    public static function make(
        RefundType $type,
        RefundReason $reason,
        ?string $reasonDetail = null,
        array $items = [],
        float $freightAmount = 0
    ): self {
        return new self(
            type: $type,
            reason: $reason,
            reasonDetail: $reasonDetail,
            items: $items,
            freightAmount: $freightAmount,
        );
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'reason' => $this->reason,
            'reason_detail' => $this->reasonDetail,
            'freight_amount' => $this->freightAmount,
            'items' => array_map(static fn (RefundItemData $item) => $item->toArray(), $this->items),
        ];
    }
}
