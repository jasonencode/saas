<?php

namespace App\Services\Mall\DTOs;

use App\Contracts\Orderable;
use Exception;
use Illuminate\Contracts\Support\Arrayable;
use RuntimeException;

/**
 * 订单明细 DTO
 *
 * 持有一个可订购主体（Orderable）与购买数量，
 * 将校验、定价、库存扣减等职责委托给可订购主体自身。
 */
class OrderItemDto implements Arrayable
{
    /**
     * 所属租户 ID（由可订购主体解析得出）
     */
    public int $tenantId;

    /**
     * 下单单价快照
     */
    public string $price;

    /**
     * 可订购主体实例
     */
    public Orderable $orderable;

    /**
     * @param  Orderable  $orderable  可订购主体
     * @param  int  $qty  购买数量
     * @param  string|null  $remark  备注
     *
     * @throws RuntimeException 当可订购主体不可购买或库存不足时
     */
    public function __construct(
        Orderable $orderable,
        public int $qty = 1,
        public ?string $remark = null
    ) {
        if ($qty < 1) {
            throw new RuntimeException('购买数量必须大于 0');
        }

        $this->orderable = $orderable;

        // 委托给可订购主体做业务校验
        if ($reason = $orderable->checkOrderable($qty)) {
            throw new RuntimeException($reason);
        }

        $this->tenantId = $orderable->getTenantId();
        $this->price = $orderable->getOrderablePrice();
    }

    /**
     * 创建订单明细 DTO
     *
     * @param  Orderable  $orderable  可订购主体
     * @param  int  $qty  购买数量
     * @param  string|null  $remark  备注
     *
     * @throws RuntimeException|Exception 当可订购主体不可购买或库存不足时
     *
     * @return self 订单明细 DTO
     */
    public static function make(Orderable $orderable, int $qty = 1, ?string $remark = null): self
    {
        return new self($orderable, $qty, $remark);
    }

    /**
     * 转换为数组
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'orderable_type' => $this->orderable::class,
            'orderable_id' => $this->orderable->getKey(),
            'orderable_name' => $this->orderable->getOrderableName(),
            'price' => $this->price,
            'qty' => $this->qty,
            'remark' => $this->remark,
            'amount' => $this->getAmount(),
        ];
    }

    /**
     * 获取小计金额
     *
     * @return string 金额（保留两位小数）
     */
    public function getAmount(): string
    {
        return bcmul($this->price, (string) $this->qty, 2);
    }

    /**
     * 获取运费
     *
     * 默认可订购主体不产生运费（虚拟商品、充值、预约等），
     * 实体商品可由运费计算策略在更外层覆盖。
     *
     * @return string 运费金额
     */
    public function getFreight(): string
    {
        return '0.00';
    }
}
