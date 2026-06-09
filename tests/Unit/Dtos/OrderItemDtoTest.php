<?php

namespace Tests\Unit\Dtos;

use App\Dtos\Order\OrderItemDto;
use App\Enums\Mall\ProductStatus;
use App\Models\Mall\Product;
use App\Models\Mall\Sku;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class OrderItemDtoTest extends TestCase
{
    private function createSku(float $price, int $stock, ProductStatus $status = ProductStatus::Up, string $productName = '测试商品'): Sku
    {
        $product = new Product();
        $product->setAttribute('id', 1);
        $product->setAttribute('tenant_id', 1);
        $product->setAttribute('name', $productName);
        $product->setAttribute('status', $status);
        $product->setAttribute('weight', 0.5);

        $sku = new Sku();
        $sku->setRelation('product', $product);
        $sku->setAttribute('id', 100);
        $sku->setAttribute('price', $price);
        $sku->setAttribute('stock', $stock);

        return $sku;
    }

    // ========================================
    // 构造函数 - 正常创建
    // ========================================

    public function test_create_with_valid_sku(): void
    {
        $sku = $this->createSku(price: 99.90, stock: 100);

        $dto = new OrderItemDto($sku, qty: 2);

        $this->assertEquals(2, $dto->qty);
        $this->assertEquals('99.90', $dto->price);
        $this->assertEquals(1, $dto->tenantId);
    }

    public function test_create_with_remark(): void
    {
        $sku = $this->createSku(price: 10.00, stock: 50);

        $dto = new OrderItemDto($sku, qty: 1, remark: '请用红色包装');

        $this->assertSame('请用红色包装', $dto->remark);
    }

    public function test_create_sets_default_qty_to_one(): void
    {
        $sku = $this->createSku(price: 10.00, stock: 50);

        $dto = new OrderItemDto($sku);

        $this->assertEquals(1, $dto->qty);
    }

    public function test_make_static_constructor(): void
    {
        $sku = $this->createSku(price: 25.00, stock: 30);

        $dto = OrderItemDto::make($sku, qty: 3, remark: '测试');

        $this->assertInstanceOf(OrderItemDto::class, $dto);
        $this->assertEquals(3, $dto->qty);
    }

    // ========================================
    // 构造函数 - 异常场景
    // ========================================

    public function test_throws_when_product_is_offline(): void
    {
        $sku = $this->createSku(price: 10.00, stock: 50, status: ProductStatus::Down);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/已下架/');

        new OrderItemDto($sku, qty: 1);
    }

    public function test_throws_when_stock_insufficient(): void
    {
        $sku = $this->createSku(price: 10.00, stock: 2);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/库存不足/');

        new OrderItemDto($sku, qty: 5);
    }

    // ========================================
    // getAmount - 金额计算
    // ========================================

    public function test_get_amount_single_item(): void
    {
        $sku = $this->createSku(price: 99.90, stock: 10);
        $dto = new OrderItemDto($sku, qty: 1);

        $this->assertSame('99.90', $dto->getAmount());
    }

    public function test_get_amount_multiple_items(): void
    {
        $sku = $this->createSku(price: 50.00, stock: 100);
        $dto = new OrderItemDto($sku, qty: 3);

        $this->assertSame('150.00', $dto->getAmount());
    }

    public function test_get_amount_with_decimal_price(): void
    {
        $sku = $this->createSku(price: 33.33, stock: 100);
        $dto = new OrderItemDto($sku, qty: 2);

        $this->assertSame('66.66', $dto->getAmount());
    }

    public function test_get_amount_zero_price(): void
    {
        $sku = $this->createSku(price: 0.00, stock: 100);
        $dto = new OrderItemDto($sku, qty: 5);

        $this->assertSame('0.00', $dto->getAmount());
    }

    // ========================================
    // getFreight - 运费
    // ========================================

    public function test_get_freight_returns_zero(): void
    {
        $sku = $this->createSku(price: 10.00, stock: 10);
        $dto = new OrderItemDto($sku, qty: 1);

        $this->assertSame('0.00', $dto->getFreight());
    }

    // ========================================
    // toArray - 序列化
    // ========================================

    public function test_to_array_structure(): void
    {
        $sku = $this->createSku(price: 29.90, stock: 50, productName: '测试T恤');
        $dto = new OrderItemDto($sku, qty: 2, remark: 'L码');

        $array = $dto->toArray();

        $this->assertArrayHasKey('tenant_id', $array);
        $this->assertArrayHasKey('product_id', $array);
        $this->assertArrayHasKey('sku_id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('price', $array);
        $this->assertArrayHasKey('qty', $array);
        $this->assertArrayHasKey('remark', $array);
        $this->assertArrayHasKey('amount', $array);
        $this->assertArrayHasKey('freight', $array);
    }

    public function test_to_array_values(): void
    {
        $sku = $this->createSku(price: 29.90, stock: 50, productName: '测试T恤');
        $dto = new OrderItemDto($sku, qty: 2, remark: 'L码');

        $array = $dto->toArray();

        $this->assertEquals(1, $array['tenant_id']);
        $this->assertEquals(1, $array['product_id']);
        $this->assertEquals(100, $array['sku_id']);
        $this->assertSame('测试T恤', $array['name']);
        $this->assertSame('29.90', $array['price']);
        $this->assertEquals(2, $array['qty']);
        $this->assertSame('L码', $array['remark']);
        $this->assertSame('59.80', $array['amount']);
        $this->assertSame('0.00', $array['freight']);
    }
}
