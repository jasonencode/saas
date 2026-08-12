<?php

namespace App\Services\Mall\DTOs;

use App\Enums\Mall\RefundReason;
use App\Enums\Mall\RefundType;
use Illuminate\Contracts\Support\Arrayable;
use InvalidArgumentException;

/**
 * 退款申请数据 DTO
 *
 * 封装退款表单提交的全部数据，构造时完成：
 * - 枚举归一（字符串 → 枚举实例）
 * - 字段校验（类型/原因/原因详情/商品列表）
 * - 商品明细标准化（RefundItemData）
 */
class RefundData implements Arrayable
{
    /**
     * @param  RefundType  $type  退款类型
     * @param  RefundReason  $reason  退款原因
     * @param  string|null  $reasonDetail  原因详情
     * @param  RefundItemData[]  $items  退款商品列表
     */
    public function __construct(
        public RefundType $type,
        public RefundReason $reason,
        public ?string $reasonDetail,
        public array $items,
    ) {}

    /**
     * 从原始数组创建退款数据 DTO
     *
     * @param  array{type: RefundType|string, reason: RefundReason|string, reason_detail?: string, items: array<int, array{order_item_id: int|string, qty: int|string, remark?: string, price?: string|int|float}>}  $data  原始退款数据
     */
    public static function make(array $data): self
    {
        $type = self::normalizeType($data['type'] ?? null);
        $reason = self::normalizeReason($data['reason'] ?? null);

        self::validateReasonForType($type, $reason);

        $reasonDetail = $data['reason_detail'] ?? null;
        self::validateReasonDetail($reason, $reasonDetail);

        $items = self::normalizeItems($data['items'] ?? []);

        return new self(
            type: $type,
            reason: $reason,
            reasonDetail: $reasonDetail,
            items: $items,
        );
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'reason' => $this->reason,
            'reason_detail' => $this->reasonDetail,
            'items' => array_map(static fn (RefundItemData $item) => $item->toArray(), $this->items),
        ];
    }

    private static function normalizeType(RefundType|string|null $type): RefundType
    {
        if (is_string($type)) {
            $type = RefundType::tryFrom($type);
        }

        if (!$type instanceof RefundType) {
            throw new InvalidArgumentException('退款类型不合法');
        }

        return $type;
    }

    private static function normalizeReason(RefundReason|string|null $reason): RefundReason
    {
        if (is_string($reason)) {
            $reason = RefundReason::tryFrom($reason);
        }

        if (!$reason instanceof RefundReason) {
            throw new InvalidArgumentException('退款原因不合法');
        }

        return $reason;
    }

    private static function validateReasonForType(RefundType $type, RefundReason $reason): void
    {
        if (!array_key_exists($reason->value, $type->reasons())) {
            throw new InvalidArgumentException('该退款类型不支持此退款原因');
        }
    }

    private static function validateReasonDetail(RefundReason $reason, ?string $detail): void
    {
        if ($reason === RefundReason::Other && (!is_string($detail) || trim($detail) === '')) {
            throw new InvalidArgumentException('选择"其他"原因时必须填写原因详情');
        }

        if ($detail !== null && mb_strlen($detail) > 500) {
            throw new InvalidArgumentException('退款原因详情不能超过500个字符');
        }
    }

    /**
     * @param  array<int, array{order_item_id: int|string, qty: int|string, remark?: string, price?: string|int|float}>  $items
     *
     * @return RefundItemData[]
     */
    private static function normalizeItems(array $items): array
    {
        if ($items === []) {
            throw new InvalidArgumentException('请至少选择一个退款商品');
        }

        return array_map(static fn (array $item) => RefundItemData::make($item), $items);
    }
}
