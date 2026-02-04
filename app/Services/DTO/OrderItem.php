<?php

namespace App\Factories\Order;

use App\Models\Address;
use Exception;
use Illuminate\Contracts\Support\Arrayable;
use App\Enums\Mall\ProductStatus;
use App\Models\Mall\Product;
use App\Models\Mall\Sku;

class OrderItem implements Arrayable
{
    public int $tenantId;

    public string $price;

    public Product $product;

    /**
     * @throws Exception
     */
    public function __construct(
        public Sku $sku,
        public int $qty = 1,
        public ?string $remark = null
    ) {
        if ($sku->product->status != ProductStatus::Up) {
            throw new Exception(
                sprintf(
                    '商品[%s]规格[%s]已下架或不可购买',
                    $this->sku->product->name,
                    ''),
            );
        }

        if ($sku->stocks < $qty) {
            throw new Exception(
                sprintf(
                    '商品[%s]规格[%s]库存不足',
                    $this->sku->product->name,
                    ''
                )
            );
        }

        $this->tenantId = $this->sku->product->tenant_id;
        $this->product = $this->sku->product;
        $this->price = $this->sku->price;
    }

    public static function make(Sku $sku, int $qty = 1, ?string $remark = null): OrderItem
    {
        return new self($sku, $qty, $remark);
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'product_id' => $this->product->id,
            'sku_id' => $this->sku->id,
            'name' => $this->sku->product->name,
            'price' => $this->price,
            'qty' => $this->qty,
            'remark' => $this->remark,
            'amount' => $this->getAmount(),
            'freight' => $this->getFreight(),
        ];
    }

    public function getAmount(): string
    {
        return bcmul($this->price, (string) $this->qty, 2);
    }

    public function getFreight(?Address $address = null): string
    {
        // 当前数据结构未提供商品配送模板与运输单位，默认返回 0 运费
        // 如果将来提供配送模板，可在此根据地址与模板计算实际运费
        return '0.00';
    }
}
