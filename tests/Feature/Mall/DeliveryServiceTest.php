<?php

namespace Tests\Feature\Mall;

use App\Models\Mall\Delivery;
use App\Models\Mall\DeliveryRule;
use App\Models\Mall\Sku;
use App\Services\Mall\DeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class DeliveryServiceTest extends TestCase
{
    use RefreshDatabase;

    private DeliveryService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(DeliveryService::class);
    }

    /**
     * 创建模拟商品项
     */
    private function createItems(array $itemsData): Collection
    {
        return collect($itemsData)->map(function ($data) {
            return (object) [
                'qty' => $data['qty'] ?? 1,
                'product' => (object) [
                    'weight' => $data['weight'] ?? 0,
                ],
            ];
        });
    }

    /**
     * 创建带 SKU 重量/体积的模拟订单项
     *
     * 用于验证重量/体积取自 SKU 而非商品。返回对象形如：
     * { qty, product: { delivery_id }, sku: Sku(weight, volume) }
     */
    private function createItemsWithSku(array $itemsData): Collection
    {
        return collect($itemsData)->map(function ($data) {
            $sku = new Sku;
            $sku->weight = $data['weight'] ?? 0;
            $sku->volume = $data['volume'] ?? 0;

            return (object) [
                'qty' => $data['qty'] ?? 1,
                'sku' => $sku,
            ];
        });
    }

    /**
     * 创建模拟商品项（带 getAmount 方法）
     */
    private function createItemsWithAmount(array $itemsData): Collection
    {
        return collect($itemsData)->map(function ($data) {
            return new class($data) {
                public int $qty;

                public object $product;

                public function __construct(array $data)
                {
                    $this->qty = $data['qty'] ?? 1;
                    $this->product = (object) ['weight' => $data['weight'] ?? 0];
                }

                public function getAmount(): float
                {
                    return $this->product->weight > 0
                        ? $this->product->weight * $this->qty
                        : $this->qty * 10.0;
                }
            };
        });
    }

    // ========================================
    // getDefaultForTenant
    // ========================================

    public function test_get_default_for_tenant_returns_default_delivery(): void
    {
        $delivery = Delivery::factory()->asDefault()->create(['tenant_id' => 1]);

        $result = $this->service->getDefaultForTenant(1);

        $this->assertNotNull($result);
        $this->assertEquals($delivery->id, $result->id);
    }

    public function test_get_default_for_tenant_returns_null_when_none(): void
    {
        Delivery::factory()->count(3)->create(['tenant_id' => 1, 'is_default' => false]);

        $result = $this->service->getDefaultForTenant(1);

        $this->assertNull($result);
    }

    public function test_get_default_for_tenant_ignores_disabled(): void
    {
        Delivery::factory()->disabled()->asDefault()->create(['tenant_id' => 1]);

        $result = $this->service->getDefaultForTenant(1);

        $this->assertNull($result);
    }

    public function test_get_default_for_tenant_ignores_other_tenants(): void
    {
        Delivery::factory()->asDefault()->create(['tenant_id' => 2]);

        $result = $this->service->getDefaultForTenant(1);

        $this->assertNull($result);
    }

    // ========================================
    // calculateOrderFreight - 基础计算
    // ========================================

    public function test_disabled_delivery_returns_zero(): void
    {
        $delivery = Delivery::factory()->disabled()->create();
        $items = $this->createItems([['qty' => 1]]);

        $freight = $this->service->calculateOrderFreight($delivery, $items);

        $this->assertEquals(0.00, $freight);
    }

    public function test_empty_items_returns_zero(): void
    {
        $delivery = Delivery::factory()->create();
        $items = collect();

        $freight = $this->service->calculateOrderFreight($delivery, $items);

        $this->assertEquals(0.00, $freight);
    }

    // ========================================
    // calculateOrderFreight - 按数量计费
    // ========================================

    public function test_count_type_first_item_only(): void
    {
        $delivery = Delivery::factory()->countType()->create([
            'first' => 1,
            'first_fee' => 10.00,
            'additional' => 1,
            'additional_fee' => 5.00,
        ]);
        $items = $this->createItems([['qty' => 1]]);

        $freight = $this->service->calculateOrderFreight($delivery, $items);

        // 1 件，未超过首件，运费 = 首费 10
        $this->assertEquals(10.00, $freight);
    }

    public function test_count_type_additional_items(): void
    {
        $delivery = Delivery::factory()->countType()->create([
            'first' => 1,
            'first_fee' => 10.00,
            'additional' => 1,
            'additional_fee' => 5.00,
        ]);
        $items = $this->createItems([['qty' => 3]]);

        $freight = $this->service->calculateOrderFreight($delivery, $items);

        // 3 件，首件 10，超出 2 件，每件 5 → 10 + 2*5 = 20
        $this->assertEquals(20.00, $freight);
    }

    public function test_count_type_additional_items_rounds_up_units(): void
    {
        $delivery = Delivery::factory()->countType()->create([
            'first' => 1,
            'first_fee' => 10.00,
            'additional' => 2,
            'additional_fee' => 5.00,
        ]);
        $items = $this->createItems([['qty' => 4]]);

        $freight = $this->service->calculateOrderFreight($delivery, $items);

        $this->assertSame('20.00', $freight);
    }

    public function test_count_type_multiple_items(): void
    {
        $delivery = Delivery::factory()->countType()->create([
            'first' => 2,
            'first_fee' => 10.00,
            'additional' => 1,
            'additional_fee' => 3.00,
        ]);
        $items = $this->createItems([
            ['qty' => 2],
            ['qty' => 3],
        ]);

        $freight = $this->service->calculateOrderFreight($delivery, $items);

        // 共 5 件，首 2 件 10，超出 3 件，每件 3 → 10 + 3*3 = 19
        $this->assertEquals(19.00, $freight);
    }

    // ========================================
    // calculateOrderFreight - 按重量计费
    // ========================================

    public function test_weight_type_within_first_weight(): void
    {
        $delivery = Delivery::factory()->weight()->create([
            'first' => 1,      // 首重 1kg
            'first_fee' => 10.00,
            'additional' => 1,  // 续重 1kg
            'additional_fee' => 5.00,
        ]);
        // 商品重量 0.5kg × 1 件 = 0.5kg = 500g
        $items = $this->createItems([['qty' => 1, 'weight' => 0.5]]);

        $freight = $this->service->calculateOrderFreight($delivery, $items);

        $this->assertEquals(10.00, $freight);
    }

    public function test_weight_type_exceeds_first_weight(): void
    {
        $delivery = Delivery::factory()->weight()->create([
            'first' => 1,      // 首重 1kg
            'first_fee' => 10.00,
            'additional' => 1,  // 续重 1kg
            'additional_fee' => 5.00,
        ]);
        // 商品重量 0.8kg × 2 件 = 1.6kg = 1600g
        $items = $this->createItems([
            ['qty' => 2, 'weight' => 0.8],
        ]);

        $freight = $this->service->calculateOrderFreight($delivery, $items);

        // 首重 1kg → 10 元，超出 0.6kg → ceil(0.6/1) = 1 续重 → 5 元
        // 总计 10 + 1*5 = 15
        $this->assertEquals(15.00, $freight);
    }

    // ========================================
    // calculateOrderFreight - 包邮门槛
    // ========================================

    public function test_free_shipping_when_threshold_met(): void
    {
        $delivery = Delivery::factory()->countType()
            ->withFreeShipping(100.00)
            ->create([
                'first' => 1,
                'first_fee' => 10.00,
                'additional' => 1,
                'additional_fee' => 5.00,
            ]);
        $items = $this->createItemsWithAmount([
            ['qty' => 10, 'weight' => 0],
        ]);

        $freight = $this->service->calculateOrderFreight($delivery, $items);

        $this->assertEquals(0.00, $freight);
    }

    public function test_not_free_shipping_when_below_threshold(): void
    {
        $delivery = Delivery::factory()->countType()
            ->withFreeShipping(100.00)
            ->create([
                'first' => 1,
                'first_fee' => 10.00,
                'additional' => 1,
                'additional_fee' => 5.00,
            ]);
        $items = $this->createItemsWithAmount([
            ['qty' => 2, 'weight' => 0],
        ]);

        $freight = $this->service->calculateOrderFreight($delivery, $items);

        // getAmount → 2 * 10 = 20, 未达 100，不包邮
        // 2 件，首件 10，超出 1 件 5 → 15
        $this->assertEquals(15.00, $freight);
    }

    // ========================================
    // calculateOrderFreight - 配送规则匹配
    // ========================================

    public function test_rule_by_district_match(): void
    {
        $delivery = Delivery::factory()->countType()->create([
            'first' => 1,
            'first_fee' => 10.00,
            'additional' => 1,
            'additional_fee' => 5.00,
        ]);

        // 区县级规则
        DeliveryRule::factory()->create([
            'delivery_id' => $delivery->id,
            'province_id' => 1,
            'city_id' => 10,
            'district_id' => 100,
            'first' => 1,
            'first_fee' => 20.00,
            'additional' => 1,
            'additional_fee' => 8.00,
        ]);

        $items = $this->createItems([['qty' => 1]]);

        $freight = $this->service->calculateOrderFreight($delivery, $items, 1, 10, 100);

        // 匹配区县规则，首费 20
        $this->assertEquals(20.00, $freight);
    }

    public function test_rule_by_city_match(): void
    {
        $delivery = Delivery::factory()->countType()->create([
            'first' => 1,
            'first_fee' => 10.00,
            'additional' => 1,
            'additional_fee' => 5.00,
        ]);

        // 市级规则（district_id 为 null）
        DeliveryRule::factory()->create([
            'delivery_id' => $delivery->id,
            'province_id' => 1,
            'city_id' => 10,
            'district_id' => null,
            'first' => 1,
            'first_fee' => 15.00,
            'additional' => 1,
            'additional_fee' => 6.00,
        ]);

        $items = $this->createItems([['qty' => 1]]);

        $freight = $this->service->calculateOrderFreight($delivery, $items, 1, 10);

        $this->assertEquals(15.00, $freight);
    }

    public function test_rule_by_province_match(): void
    {
        $delivery = Delivery::factory()->countType()->create([
            'first' => 1,
            'first_fee' => 10.00,
            'additional' => 1,
            'additional_fee' => 5.00,
        ]);

        // 省级规则
        DeliveryRule::factory()->create([
            'delivery_id' => $delivery->id,
            'province_id' => 1,
            'city_id' => null,
            'district_id' => null,
            'first' => 1,
            'first_fee' => 12.00,
            'additional' => 1,
            'additional_fee' => 5.00,
        ]);

        $items = $this->createItems([['qty' => 1]]);

        $freight = $this->service->calculateOrderFreight($delivery, $items, 1);

        $this->assertEquals(12.00, $freight);
    }

    public function test_no_matching_rule_uses_default(): void
    {
        $delivery = Delivery::factory()->countType()->create([
            'first' => 1,
            'first_fee' => 10.00,
            'additional' => 1,
            'additional_fee' => 5.00,
        ]);

        // 规则指定了其他地区
        DeliveryRule::factory()->create([
            'delivery_id' => $delivery->id,
            'province_id' => 99,
            'city_id' => 999,
            'district_id' => 9999,
            'first' => 1,
            'first_fee' => 100.00,
        ]);

        $items = $this->createItems([['qty' => 1]]);

        // 传入地区 1, 10, 100 不匹配规则 99, 999, 9999
        $freight = $this->service->calculateOrderFreight($delivery, $items, 1, 10, 100);

        // 使用模板默认值
        $this->assertEquals(10.00, $freight);
    }

    public function test_rule_free_shipping_threshold(): void
    {
        $delivery = Delivery::factory()->countType()->create([
            'first' => 1,
            'first_fee' => 10.00,
            'additional' => 1,
            'additional_fee' => 5.00,
        ]);

        DeliveryRule::factory()
            ->withFreeShipping(50.00)
            ->create([
                'delivery_id' => $delivery->id,
                'province_id' => 1,
                'first' => 1,
                'first_fee' => 15.00,
                'additional' => 1,
                'additional_fee' => 5.00,
            ]);

        $items = $this->createItemsWithAmount([
            ['qty' => 5, 'weight' => 0],
        ]);

        $freight = $this->service->calculateOrderFreight($delivery, $items, 1);

        // getAmount → 5 * 10 = 50, 达到包邮门槛 50
        $this->assertEquals(0.00, $freight);
    }

    // ========================================
    // calculateOrderFreight - 无地区匹配使用默认
    // ========================================

    public function test_no_region_uses_default_config(): void
    {
        $delivery = Delivery::factory()->countType()->create([
            'first' => 1,
            'first_fee' => 10.00,
            'additional' => 1,
            'additional_fee' => 5.00,
        ]);

        $items = $this->createItems([['qty' => 3]]);

        // 不传地区参数
        $freight = $this->service->calculateOrderFreight($delivery, $items);

        // 3 件，首件 10，超出 2 件，每件 5 → 10 + 2*5 = 20
        $this->assertEquals(20.00, $freight);
    }

    // ========================================
    // calculateOrderFreight - 按体积计费（Size）
    // ========================================

    public function test_size_type_within_first_volume(): void
    {
        $delivery = Delivery::factory()->size()->create([
            'first' => 1,      // 首体积 1 m³
            'first_fee' => 20.00,
            'additional' => 1, // 续体积 1 m³
            'additional_fee' => 8.00,
        ]);
        // SKU 体积 0.5 m³ × 1 件 = 0.5 m³，未超首体积
        $items = $this->createItemsWithSku([['qty' => 1, 'volume' => 0.5]]);

        $freight = $this->service->calculateOrderFreight($delivery, $items);

        $this->assertEquals(20.00, $freight);
    }

    public function test_size_type_exceeds_first_volume(): void
    {
        $delivery = Delivery::factory()->size()->create([
            'first' => 1,
            'first_fee' => 20.00,
            'additional' => 1,
            'additional_fee' => 8.00,
        ]);
        // SKU 体积 0.6 m³ × 2 件 = 1.2 m³，超首体积 0.2 → ceil(0.2/1)=1 续体积
        // 20 + 1*8 = 28
        $items = $this->createItemsWithSku([['qty' => 2, 'volume' => 0.6]]);

        $freight = $this->service->calculateOrderFreight($delivery, $items);

        $this->assertEquals(28.00, $freight);
    }

    public function test_size_type_multiple_skus_summed(): void
    {
        $delivery = Delivery::factory()->size()->create([
            'first' => 2,      // 首体积 2 m³
            'first_fee' => 30.00,
            'additional' => 1, // 续体积 1 m³
            'additional_fee' => 10.00,
        ]);
        // 两个 SKU：1.0 + 1.5 = 2.5 m³，超首体积 0.5 → 1 续体积
        // 30 + 1*10 = 40
        $items = $this->createItemsWithSku([
            ['qty' => 1, 'volume' => 1.0],
            ['qty' => 1, 'volume' => 1.5],
        ]);

        $freight = $this->service->calculateOrderFreight($delivery, $items);

        $this->assertEquals(40.00, $freight);
    }

    // ========================================
    // calculateOrderFreight - SKU 重量回归
    // ========================================

    public function test_weight_type_uses_sku_weight(): void
    {
        $delivery = Delivery::factory()->weight()->create([
            'first' => 1,      // 首重 1 kg
            'first_fee' => 10.00,
            'additional' => 1, // 续重 1 kg
            'additional_fee' => 5.00,
        ]);
        // SKU 重量 1.2 kg × 1 件 = 1.2 kg = 1200 g，超首重 200 g → 1 续重
        // 10 + 1*5 = 15
        $items = $this->createItemsWithSku([['qty' => 1, 'weight' => 1.2]]);

        $freight = $this->service->calculateOrderFreight($delivery, $items);

        $this->assertEquals(15.00, $freight);
    }

    public function test_weight_type_sums_multiple_sku_weights(): void
    {
        $delivery = Delivery::factory()->weight()->create([
            'first' => 3,      // 首重 3 kg
            'first_fee' => 15.00,
            'additional' => 1, // 续重 1 kg
            'additional_fee' => 4.00,
        ]);
        // SKU A: 1.0 kg × 2 = 2.0 kg；SKU B: 2.5 kg × 1 = 2.5 kg
        // 合计 4.5 kg = 4500 g，超首重 1500 g → ceil(1.5/1)=2 续重
        // 15 + 2*4 = 23
        $items = $this->createItemsWithSku([
            ['qty' => 2, 'weight' => 1.0],
            ['qty' => 1, 'weight' => 2.5],
        ]);

        $freight = $this->service->calculateOrderFreight($delivery, $items);

        $this->assertEquals(23.00, $freight);
    }
}
