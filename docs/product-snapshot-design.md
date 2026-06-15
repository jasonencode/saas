# 商品快照模型设计方案

> 创建时间：2026-06-15
> 状态：设计中

---

## 一、设计目标

1. 保存下单时完整的商品信息，防止商品修改影响历史订单
2. 结构清晰，易于扩展
3. 支持快照复用，减少数据冗余

---

## 二、数据库设计

### 2.1 product_snapshots 表（商品快照）

```sql
CREATE TABLE product_snapshots (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    product_id BIGINT NOT NULL COMMENT '原始商品ID',
    tenant_id BIGINT NOT NULL COMMENT '租户ID',
    
    -- 基础信息
    name VARCHAR(255) NOT NULL COMMENT '商品名称',
    description TEXT COMMENT '商品简介',
    cover VARCHAR(255) COMMENT '封面图',
    pictures JSON COMMENT '轮播图',
    
    -- 分类品牌
    category_id BIGINT COMMENT '分类ID',
    category_name VARCHAR(255) COMMENT '分类名称快照',
    brand_id BIGINT COMMENT '品牌ID',
    brand_name VARCHAR(255) COMMENT '品牌名称快照',
    
    -- 扩展信息
    ext JSON COMMENT '扩展信息',
    
    -- 快照元数据
    snapshot_hash VARCHAR(64) COMMENT '快照哈希，用于判断是否需要新建快照',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_product_id (product_id),
    INDEX idx_tenant_id (tenant_id),
    INDEX idx_snapshot_hash (snapshot_hash)
) COMMENT='商品快照表';
```

### 2.2 sku_snapshots 表（SKU 快照）

```sql
CREATE TABLE sku_snapshots (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    sku_id BIGINT NOT NULL COMMENT '原始SKU ID',
    product_snapshot_id BIGINT NOT NULL COMMENT '关联的商品快照ID',
    
    -- SKU 信息
    name VARCHAR(255) NOT NULL COMMENT '规格名称',
    code VARCHAR(32) COMMENT '规格编号',
    cover VARCHAR(255) COMMENT '规格封面图',
    origin_price DECIMAL(10,2) UNSIGNED DEFAULT 0 COMMENT '原价',
    price DECIMAL(10,2) UNSIGNED DEFAULT 0 COMMENT '销售价',
    
    -- 快照元数据
    snapshot_hash VARCHAR(64) COMMENT '快照哈希',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_sku_id (sku_id),
    INDEX idx_product_snapshot_id (product_snapshot_id),
    INDEX idx_snapshot_hash (snapshot_hash)
) COMMENT='SKU 快照表';
```

### 2.3 order_items 表（修改）

```sql
-- 移除快照字段，改为引用快照表
ALTER TABLE order_items
    DROP COLUMN product_name,
    DROP COLUMN sku_name,
    DROP COLUMN cover,
    ADD COLUMN product_snapshot_id BIGINT AFTER sku_id COMMENT '商品快照ID',
    ADD COLUMN sku_snapshot_id BIGINT AFTER product_snapshot_id COMMENT 'SKU快照ID',
    ADD INDEX idx_product_snapshot_id (product_snapshot_id),
    ADD INDEX idx_sku_snapshot_id (sku_snapshot_id);
```

---

## 三、模型设计

### 3.1 ProductSnapshot 模型

```php
<?php

namespace App\Models\Mall;

use App\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductSnapshot extends Model
{
    protected $casts = [
        'pictures' => 'json',
        'ext' => 'json',
    ];

    /**
     * 关联原始商品
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    /**
     * 关联 SKU 快照
     */
    public function skuSnapshots(): HasMany
    {
        return $this->hasMany(SkuSnapshot::class);
    }

    /**
     * 关联租户
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * 生成或获取商品快照
     */
    public static function snapshot(Product $product): self
    {
        $hash = self::generateHash($product);
        
        // 查找现有快照
        $existing = static::where('product_id', $product->id)
            ->where('snapshot_hash', $hash)
            ->first();
            
        if ($existing) {
            return $existing;
        }
        
        // 创建新快照
        return static::create([
            'product_id' => $product->id,
            'tenant_id' => $product->tenant_id,
            'name' => $product->name,
            'description' => $product->description,
            'cover' => $product->cover,
            'pictures' => $product->pictures,
            'category_id' => $product->category_id,
            'category_name' => $product->category?->name,
            'brand_id' => $product->brand_id,
            'brand_name' => $product->brand?->name,
            'ext' => $product->ext,
            'snapshot_hash' => $hash,
        ]);
    }

    /**
     * 生成快照哈希
     */
    protected static function generateHash(Product $product): string
    {
        $data = [
            $product->name,
            $product->description,
            $product->cover,
            $product->pictures,
            $product->category_id,
            $product->brand_id,
            $product->ext,
        ];
        
        return md5(serialize($data));
    }
}
```

### 3.2 SkuSnapshot 模型

```php
<?php

namespace App\Models\Mall;

use App\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkuSnapshot extends Model
{
    protected $casts = [
        'origin_price' => 'decimal:2',
        'price' => 'decimal:2',
    ];

    /**
     * 关联原始 SKU
     */
    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class)->withTrashed();
    }

    /**
     * 关联商品快照
     */
    public function productSnapshot(): BelongsTo
    {
        return $this->belongsTo(ProductSnapshot::class);
    }

    /**
     * 生成或获取 SKU 快照
     */
    public static function snapshot(Sku $sku, ProductSnapshot $productSnapshot): self
    {
        $hash = self::generateHash($sku);
        
        // 查找现有快照
        $existing = static::where('sku_id', $sku->id)
            ->where('product_snapshot_id', $productSnapshot->id)
            ->where('snapshot_hash', $hash)
            ->first();
            
        if ($existing) {
            return $existing;
        }
        
        // 创建新快照
        return static::create([
            'sku_id' => $sku->id,
            'product_snapshot_id' => $productSnapshot->id,
            'name' => $sku->name,
            'code' => $sku->code,
            'cover' => $sku->cover,
            'origin_price' => $sku->origin_price,
            'price' => $sku->price,
            'snapshot_hash' => $hash,
        ]);
    }

    /**
     * 生成快照哈希
     */
    protected static function generateHash(Sku $sku): string
    {
        $data = [
            $sku->name,
            $sku->code,
            $sku->cover,
            $sku->origin_price,
            $sku->price,
        ];
        
        return md5(serialize($data));
    }
}
```

### 3.3 OrderItem 模型（修改）

```php
<?php

namespace App\Models\Mall;

use App\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    /**
     * 关联商品快照
     */
    public function productSnapshot(): BelongsTo
    {
        return $this->belongsTo(ProductSnapshot::class);
    }

    /**
     * 关联 SKU 快照
     */
    public function skuSnapshot(): BelongsTo
    {
        return $this->belongsTo(SkuSnapshot::class);
    }

    /**
     * 获取商品名称（兼容旧代码）
     */
    public function getProductNameAttribute(): string
    {
        return $this->productSnapshot?->name ?? '';
    }

    /**
     * 获取规格名称（兼容旧代码）
     */
    public function getSkuNameAttribute(): string
    {
        return $this->skuSnapshot?->name ?? '';
    }

    /**
     * 获取封面图（兼容旧代码）
     */
    public function getCoverAttribute(): ?string
    {
        return $this->skuSnapshot?->cover ?? $this->productSnapshot?->cover;
    }
}
```

---

## 四、快照服务

```php
<?php

namespace App\Services\Mall;

use App\Models\Mall\Product;
use App\Models\Mall\Sku;
use App\Models\Mall\ProductSnapshot;
use App\Models\Mall\SkuSnapshot;

class SnapshotService
{
    /**
     * 创建商品和 SKU 快照
     */
    public static function create(Product $product, Sku $sku): array
    {
        $productSnapshot = ProductSnapshot::snapshot($product);
        $skuSnapshot = SkuSnapshot::snapshot($sku, $productSnapshot);
        
        return [
            'product_snapshot_id' => $productSnapshot->id,
            'sku_snapshot_id' => $skuSnapshot->id,
        ];
    }
    
    /**
     * 批量创建快照
     */
    public static function createBulk(array $items): array
    {
        $snapshots = [];
        
        foreach ($items as $item) {
            $snapshots[] = self::create(
                $item['product'],
                $item['sku']
            );
        }
        
        return $snapshots;
    }
}
```

---

## 五、使用示例

### 5.1 创建订单时生成快照

```php
// OrderService.php
public function createOrder(User $user, array $items, $address = null)
{
    return DB::transaction(function () use ($user, $items, $address) {
        $order = Order::create([...]);
        
        foreach ($items as $item) {
            // 创建快照
            $snapshot = SnapshotService::create($item['product'], $item['sku']);
            
            // 创建订单项
            $order->items()->create([
                'product_snapshot_id' => $snapshot['product_snapshot_id'],
                'sku_snapshot_id' => $snapshot['sku_snapshot_id'],
                'price' => $item['sku']->price,
                'qty' => $item['qty'],
            ]);
        }
        
        return $order;
    });
}
```

### 5.2 查询订单详情

```php
$order = Order::with([
    'items.productSnapshot',
    'items.skuSnapshot',
])->find($orderId);

foreach ($order->items as $item) {
    echo $item->productSnapshot->name;  // 商品名称
    echo $item->skuSnapshot->name;      // 规格名称
    echo $item->skuSnapshot->cover;     // 规格封面
    echo $item->price;                  // 下单时价格
}
```

---

## 六、迁移计划

### 步骤 1：创建快照表
```bash
php artisan make:migration create_product_snapshots_table
php artisan make:migration create_sku_snapshots_table
```

### 步骤 2：创建快照模型
- ProductSnapshot
- SkuSnapshot

### 步骤 3：修改 order_items 表
- 添加 snapshot_id 字段
- 创建数据迁移脚本（为现有订单生成快照）
- 删除旧快照字段

### 步骤 4：更新 OrderService
- 使用 SnapshotService 创建快照
- 更新订单创建逻辑

### 步骤 5：更新查询和展示
- 更新 Filament Infolist
- 更新 API Resource

---

## 七、优缺点总结

| 方面 | 优点 | 缺点 |
|------|------|------|
| 数据完整性 | 完整保存商品信息 | 增加存储空间 |
| 扩展性 | 易于添加快照字段 | 需要维护快照逻辑 |
| 查询性能 | 可按需加载 | 多一层关联 |
| 数据冗余 | 通过哈希去重 | 初期需要设计去重逻辑 |
| 代码复杂度 | 职责清晰 | 增加模型和服务 |
