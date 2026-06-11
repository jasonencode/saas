# 商品SKU简化方案

## 概述

将当前的多SKU模式（5张表）简化为简单的hasMany模式（2张表），移除中间属性表，直接在SKU表中存储规格信息。

## 当前架构 vs 简化后架构

### 当前架构（5张表）

```
products → skus → sku_attribute → attributes → attribute_values
```

### 简化后架构（2张表）

```
products → skus
```

## 数据库变更

### 1. 修改原迁移文件

**文件名：** `database/migrations/0003_01_00_000001_create_products_table.php`

直接修改原迁移文件，将 SKU 相关表结构简化：

```php
<?php

use App\Enums\Mall\DeductStockType;use App\Enums\Mall\ProductStatus;use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', static function (Blueprint $table) {
            $table->id();
            $table->tenant();
            $table->string('name')
                ->comment('商品名称');
            $table->string('description')
                ->nullable()
                ->comment('商品简介');
            $table->cover();
            $table->pictures();
            $table->unsignedBigInteger('category_id')
                ->index()
                ->nullable()
                ->comment('分类ID');
            $table->unsignedBigInteger('brand_id')
                ->index()
                ->nullable()
                ->comment('品牌ID');
            $table->string('deduct_stock_type', 16)
                ->default(DeductStockType::Paid->value)
                ->index()
                ->comment('库存扣减方式');
            $table->boolean('can_cart')
                ->default(false)
                ->comment('是否可以加入购物车');
            $table->string('status', 16)
                ->index()
                ->default(ProductStatus::Pending->value)
                ->comment('商品状态');
            $table->sort();
            $table->jsonb('materials')
                ->nullable()
                ->comment('商品详情，图片集');
            $table->jsonb('ext')
                ->nullable()
                ->comment('扩展信息');
            $table->unsignedBigInteger('views')
                ->default(0)
                ->comment('浏览量');
            $table->timestamps();
            $table->softDeletes()
                ->index();

            $table->index(['created_at']);
        });

        Schema::create('skus', static function (Blueprint $table) {
            $table->comment('商品SKU表');
            $table->id();
            $table->unsignedBigInteger('product_id')
                ->index()
                ->comment('商品ID');
            $table->string('name')
                ->comment('规格名称，如：红色/L');
            $table->string('code', 32)
                ->index()
                ->nullable()
                ->comment('商品编号，一般为69码');
            $table->decimal('origin_price', 10)
                ->unsigned()
                ->default(0)
                ->comment('原价格');
            $table->decimal('price', 10)
                ->unsigned()
                ->default(0)
                ->comment('销售价');
            $table->integer('stock')
                ->default(0)
                ->comment('库存');
            $table->integer('sale')
                ->default(0)
                ->comment('销量');
            $table->timestamps();
        });

        Schema::create('product_logs', static function (Blueprint $table) {
            $table->comment('商品操作日志');
            $table->id();
            $table->unsignedBigInteger('product_id')
                ->index()
                ->comment('商品ID');
            $table->nullableMorphs('user');
            $table->jsonb('records')
                ->nullable()
                ->comment('日志记录');
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_logs');
        Schema::dropIfExists('skus');
        Schema::dropIfExists('products');
    }
};
```

### 2. 新表结构说明

**skus 表：**

| 字段           | 类型              | 说明           |
|--------------|-----------------|--------------|
| id           | BIGINT UNSIGNED | 主键           |
| product_id   | BIGINT UNSIGNED | 商品ID（索引）     |
| name         | VARCHAR(255)    | 规格名称，如"红色/L" |
| code         | VARCHAR(255)    | 商品编号（索引）     |
| origin_price | DECIMAL(10,2)   | 原价格          |
| price        | DECIMAL(10,2)   | 销售价          |
| stock        | INT             | 库存           |
| sale         | INT             | 销量           |
| created_at   | TIMESTAMP       | 创建时间         |
| updated_at   | TIMESTAMP       | 更新时间         |

## 模型变更

### 1. 修改 Sku 模型

**文件：** `app/Models/Mall/Sku.php`

```php
<?php

namespace App\Models\Mall;

use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Unguarded]
class Sku extends Model
{
    protected $casts = [
        'origin_price' => 'decimal:2',
        'price' => 'decimal:2',
    ];

    /**
     * 关联商品
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
```

### 2. 删除不需要的模型

**删除以下文件：**

- `app/Models/Mall/Attribute.php`
- `app/Models/Mall/AttributeValue.php`
- `app/Models/Mall/SkuAttribute.php`

### 3. 修改 Product 模型

**文件：** `app/Models/Mall/Product.php`

```php
<?php

namespace App\Models\Mall;

// ... 其他代码保持不变

class Product extends Model implements ShouldComment
{
    // ... 其他代码保持不变

    /**
     * 获取总库存
     */
    public function getStocksAttribute(): int
    {
        return $this->skus()->sum('stock');
    }

    /**
     * 商品规格
     */
    public function skus(): HasMany
    {
        return $this->hasMany(Sku::class);
    }

    /**
     * 获取总销量
     */
    public function getSalesAttribute(): int
    {
        return $this->skus()->sum('sale');
    }

    // ... 其他代码保持不变
}
```

**注意：** 删除 `attributes()` 方法，因为不再需要。

## 表单组件变更

### 1. 移除自定义组件

**删除以下文件：**
- `app/Filament/Forms/Components/SkuField.php`
- `resources/views/filament/forms/sku.blade.php`

### 2. 修改 ProductForm

**文件：** `app/Filament/Tenant/Clusters/Mall/Resources/Products/Schemas/ProductForm.php`

使用 Filament 原生的 Repeatable 组件替代自定义 SkuField：

```php
<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Products\Schemas;

use App\Enums\Mall\DeductStockType;
use App\Enums\Mall\ProductStatus;
use App\Filament\Forms\Components\CustomUpload;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Wizard::make([
                    Wizard\Step::make('SKU配置')
                        ->components([
                            Forms\Components\Repeatable::make('skus')
                                ->label('商品规格')
                                ->relationship()
                                ->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->label('规格名称')
                                        ->placeholder('如：红色/L')
                                        ->required(),
                                    Forms\Components\TextInput::make('code')
                                        ->label('商品编码')
                                        ->placeholder('条形码/SKU编号'),
                                    Forms\Components\TextInput::make('price')
                                        ->label('销售价')
                                        ->numeric()
                                        ->required()
                                        ->suffix('元'),
                                    Forms\Components\TextInput::make('origin_price')
                                        ->label('市场价')
                                        ->numeric()
                                        ->suffix('元'),
                                    Forms\Components\TextInput::make('stock')
                                        ->label('库存')
                                        ->numeric()
                                        ->required()
                                        ->default(0),
                                    Forms\Components\TextInput::make('sale')
                                        ->label('销量')
                                        ->numeric()
                                        ->default(0)
                                        ->hidden(),
                                ])
                                ->columns(3)
                                ->defaultItems(0)
                                ->addActionLabel('添加规格')
                                ->reorderable()
                                ->columnSpanFull(),
                        ]),
                    Wizard\Step::make('base')
                        ->label('商品信息')
                        ->components([
                            Forms\Components\TextInput::make('name')
                                ->label('商品名称')
                                ->required(),
                            Forms\Components\Textarea::make('description')
                                ->label('商品简介')
                                ->rows(4)
                                ->columnSpanFull(),
                            CustomUpload::cover()
                                ->label('封面图'),
                            CustomUpload::pictures()
                                ->label('轮播图'),
                            CustomUpload::make('materials')
                                ->label('详情图集')
                                ->multiple()
                                ->columnSpanFull(),
                        ]),
                ])
                    ->columnSpan(2),
                Section::make('扩展信息')
                    ->components([
                        SelectTree::make('category_id')
                            ->label('分类')
                            ->relationship(
                                relationship: 'category',
                                titleAttribute: 'name',
                                parentAttribute: 'parent_id',
                                modifyQueryUsing: fn (Builder $query) => $query->ofEnabled(),
                                modifyChildQueryUsing: fn (Builder $query) => $query->ofEnabled(),
                            )
                            ->required()
                            ->searchable()
                            ->withCount(),
                        Forms\Components\Select::make('brand_id')
                            ->label('品牌')
                            ->native(false)
                            ->relationship(
                                name: 'brand',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query) => $query->ofEnabled()
                            )
                            ->searchable()
                            ->preload(),
                        Forms\Components\KeyValue::make('ext')
                            ->label('扩展信息')
                            ->columnSpanFull(),
                        Forms\Components\Radio::make('status')
                            ->label('商品状态')
                            ->options(ProductStatus::class)
                            ->default(ProductStatus::Up),
                        Forms\Components\Toggle::make('can_cart')
                            ->label('可加入购物车'),
                        Forms\Components\TextInput::make('sort')
                            ->label(__('backend.sort'))
                            ->required()
                            ->default(0)
                            ->helperText('数字越大越靠前')
                            ->integer(),
                        Forms\Components\Radio::make('deduct_stock_type')
                            ->label('库存扣减方式')
                            ->options(DeductStockType::class)
                            ->default(DeductStockType::Paid),
                        Forms\Components\TextInput::make('views')
                            ->label('浏览量')
                            ->integer()
                            ->default(0)
                            ->required(),
                    ]),
            ]);
    }
}
```

## 购物车变更

### 1. CartItem 模型

**文件：** `app/Models/Mall/CartItem.php`

**变更点：** 无需修改，已经关联 Sku 模型。

```php
// 保持不变
public function sku(): BelongsTo
{
    return $this->belongsTo(Sku::class);
}

public function isAvailable(): bool
{
    return $this->product &&
        $this->product->status &&
        $this->sku &&
        $this->sku->stock >= $this->qty;
}
```

### 2. CartService

**文件：** `app/Services/Mall/CartService.php`

**变更点：** 无需修改，SKU 简化后仍然正常工作。

```php
// 保持不变
public function addItem(Cart $cart, Sku $sku, int $qty): CartItem
{
    if ($sku->stock < $qty) {
        throw new RuntimeException('商品库存不足');
    }

    $item = $cart->items()
        ->where('sku_id', $sku->id)
        ->first();

    if ($item) {
        $newQty = $item->qty + $qty;
        if ($newQty > 9999) {
            throw new RuntimeException('购买数量超过限制');
        }
        $item->update(['qty' => $newQty]);
    } else {
        $item = $cart->items()->create([
            'sku_id' => $sku->id,
            'qty' => $qty,
            'price_at_add' => $sku->price,
            'selected' => true,
        ]);
    }

    return $item;
}
```

### 3. CartItemResource

**文件：** `app/Http/Resources/Mall/CartItemResource.php`

**变更点：** 需要移除 `specifications` 字段，因为简化后的 SKU 不再存储属性数组。

```php
// 修改前
'sku' => [
    'sku_id' => $this->resource->sku_id,
    'name' => $this->resource->sku?->name,
    'specifications' => $this->resource->sku?->specifications ?? [],  // 移除此字段
],

// 修改后
'sku' => [
    'sku_id' => $this->resource->sku_id,
    'name' => $this->resource->sku?->name,  // 直接从 skus 表的 name 字段获取
],
```

### 4. 购物车表结构

**文件：** `database/migrations/0003_01_01_000000_create_carts_table.php`

**变更点：** 无需修改，cart_items 表已经关联 sku_id。

```php
// 保持不变
Schema::create('cart_items', static function (Blueprint $table) {
    $table->id();
    $table->tenant();
    $table->foreignId('cart_id')->index()->constrained()->onDelete('cascade');
    $table->unsignedBigInteger('product_id')->index()->comment('商品ID');
    $table->unsignedBigInteger('sku_id')->index()->comment('SKU ID');
    $table->unsignedInteger('qty')->default(1)->comment('购买数量');
    $table->decimal('price_at_add', 10)->unsigned()->comment('加入购物车时的单价快照');
    $table->boolean('selected')->default(true)->comment('是否被选中');
    $table->timestamps();
    $table->unique(['cart_id', 'sku_id']);
});
```

### 5. 购物车前端

**购物车展示：**
- 使用 `$sku->name` 获取规格名称
- 无需修改，简化后仍然正常工作

**添加购物车：**
- 需要传入 `sku_id`
- 无需修改，简化后仍然正常工作

### 2. OrderService

**文件：** `app/Services/Mall/OrderService.php`

**变更点：** 无需修改，OrderItem 已经关联 Sku 模型。

**订单创建逻辑（保持不变）：**
```php
$order->items()->create([
    'product_id' => $item->sku->product_id,
    'sku_id' => $item->sku->id,
    'product_name' => $item->sku->product->name,
    'sku_name' => $item->sku->name,  // 直接从 skus 表的 name 字段获取
    'qty' => $item->qty,
    'price' => $item->price,
    'remark' => $item->remark,
]);
```

### 3. 订单表结构

**文件：** `database/migrations/0003_02_00_000001_create_orders_table.php`

**变更点：** 无需修改，`order_items` 表已经包含 `sku_name` 字段用于存储规格名称快照。

**order_items 表结构：**
```php
Schema::create('order_items', static function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('order_id')->index()->comment('订单ID');
    $table->unsignedBigInteger('order_shipping_id')->nullable()->index()->comment('物流ID');
    $table->unsignedBigInteger('product_id')->nullable()->index()->comment('商品ID');
    $table->unsignedBigInteger('sku_id')->nullable()->index()->comment('SKU ID');
    $table->string('product_name')->nullable()->comment('商品名称快照');
    $table->string('sku_name')->nullable()->comment('规格名称快照');  // 直接存储规格名称
    $table->unsignedInteger('qty')->comment('购买数量');
    $table->decimal('price')->unsigned()->comment('商品单价');
    $table->string('remark')->nullable()->comment('商品备注');
});
```

### 4. 订单查询

**简化前：** 获取 SKU 名称需要多表关联
```php
// 旧方式：需要关联 attributes 和 attribute_values 表
$orderItem->sku->name  // 通过 accessor 计算：$this->attributes()->get()->map(...)->implode('|')
```

**简化后：** 直接从 skus 表获取
```php
// 新方式：直接获取 name 字段
$orderItem->sku->name  // 直接返回 skus 表的 name 字段值
```

### 5. 订单列表展示

**文件：** `app/Filament/Tenant/Clusters/Mall/Resources/Orders/RelationManagers/ItemRelationManager.php`

**变更点：** 无需修改，使用的是 `order_items` 表的 `sku_name` 快照字段。

```php
// 保持不变
Tables\Columns\TextColumn::make('sku_name')
    ->label('SKU名称'),
Tables\Columns\TextColumn::make('sku.code')
    ->label('SKU编码(69码)')
    ->searchable(),
```

### 6. 订单详情页面

**文件：** `app/Filament/Tenant/Clusters/Mall/Resources/Orders/Schemas/OrderInfolist.php`

**变更点：** 无需修改，订单详情页面不直接展示 SKU 信息，而是展示订单汇总信息。

### 7. 订单 DTO

**文件：** `app/Dtos/Order/OrderItemDto.php`

**变更点：** 无需修改，DTO 已经关联 Sku 模型。

```php
// 保持不变
public Sku $sku;
public int $qty;
public float $price;
public ?string $remark;
```

### 8. 订单资源

**文件：** `app/Http/Resources/Mall/OrderItemResource.php`

**变更点：** 需要移除 `specifications` 字段，因为简化后的 SKU 不再存储属性数组。

```php
// 修改前
'sku' => [
    'sku_id' => $this->resource->sku_id,
    'name' => $this->resource->sku?->name,
    'specifications' => $this->resource->sku?->specifications ?? [],  // 移除此字段
],

// 修改后
'sku' => [
    'sku_id' => $this->resource->sku_id,
    'name' => $this->resource->sku?->name,  // 直接从 skus 表的 name 字段获取
],
```

## 请求验证变更

### 1. 修改 StoreCartItemRequest

**文件：** `app/Http/Requests/Mall/StoreCartItemRequest.php`

**变更点：** 无需修改，因为已经使用 Sku 模型验证。

### 2. 修改 OrderRequest

**文件：** `app/Http/Requests/OrderRequest.php`

**变更点：** 无需修改，因为已经使用 SkuRule 验证。

## 规则变更

### 1. 修改 SkuRule

**文件：** `app/Rules/Mall/SkuRule.php`

**变更点：** 无需修改，因为已经使用 Sku 模型验证。

## 资源变更

### 1. 修改 CartItemResource

**文件：** `app/Http/Resources/Mall/CartItemResource.php`

**变更点：** 需要修改 `sku_name` 字段的获取方式。

```php
// 修改前
'sku_name' => $this->sku->name,

// 修改后（保持不变，因为 Sku 模型已经有 name 属性）
'sku_name' => $this->sku->name,
```

### 2. 修改 OrderItemResource

**文件：** `app/Http/Resources/Mall/OrderItemResource.php`

**变更点：** 需要修改 `sku_name` 字段的获取方式。

```php
// 修改前
'sku_name' => $this->sku->name,

// 修改后（保持不变，因为 Sku 模型已经有 name 属性）
'sku_name' => $this->sku->name,
```

## 前端变更

### 1. 商品创建/编辑页面

**文件：** `app/Filament/Tenant/Clusters/Mall/Resources/Products/Schemas/ProductForm.php`

**变更点：** 简化 SKU 配置步骤。

```php
Wizard::make([
    Wizard\Step::make('SKU配置')
        ->components([
            SkuField::make('skus')
                ->label('SKU配置'),
        ]),
    Wizard\Step::make('base')
        ->label('商品信息')
        ->components([
            // ... 其他字段保持不变
        ]),
])
```

### 2. 商品详情页面

**文件：** `app/Filament/Tenant/Clusters/Mall/Resources/Products/Schemas/ProductInfolist.php`

**变更点：** 需要修改 SKU 展示方式。

```php
// 修改前
use Filament\Infolists\Components\RepeatableEntry;

RepeatableEntry::make('attributes')
    ->schema([
        TextEntry::make('name'),
        RepeatableEntry::make('values')
            ->schema([
                TextEntry::make('value'),
            ]),
    ]),

// 修改后
use Filament\Infolists\Components\RepeatableEntry;

RepeatableEntry::make('skus')
    ->schema([
        TextEntry::make('name')->label('规格'),
        TextEntry::make('price')->label('价格'),
        TextEntry::make('stock')->label('库存'),
        TextEntry::make('sale')->label('销量'),
    ]),
```

## 测试数据变更

### 1. 修改 SkuFactory

**文件：** `database/factories/Mall/SkuFactory.php`

```php
<?php

namespace Database\Factories\Mall;

use App\Models\Mall\Sku;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sku>
 */
class SkuFactory extends Factory
{
    protected $model = Sku::class;

    public function definition(): array
    {
        return [
            'product_id' => null,
            'name' => $this->faker->randomElement(['红色/S', '蓝色/M', '黑色/L']),
            'code' => $this->faker->ean13(),
            'origin_price' => $this->faker->randomFloat(2, 10, 1000),
            'price' => $this->faker->randomFloat(2, 5, 500),
            'stock' => $this->faker->numberBetween(0, 100),
            'sale' => 0,
        ];
    }
}
```

## 迁移步骤

### 步骤1：备份数据

```bash
# 备份旧表数据（如果有生产数据）
mysqldump -u username -p database_name skus attributes attribute_values sku_attribute > backup.sql
```

### 步骤2：修改迁移文件

直接修改 `database/migrations/0003_01_00_000001_create_products_table.php` 文件，将 SKU 相关表结构简化。

### 步骤3：运行迁移

```bash
# 如果是全新安装，直接运行
php artisan migrate

# 如果已有数据，需要先回滚再重新迁移
php artisan migrate:rollback --step=5
php artisan migrate
```

### 步骤4：测试功能

- 测试商品创建
- 测试商品编辑
- 测试购物车功能
- 测试订单功能

### 步骤5：删除旧文件

删除不需要的文件：

**模型文件：**
- `app/Models/Mall/Attribute.php`
- `app/Models/Mall/AttributeValue.php`
- `app/Models/Mall/SkuAttribute.php`

**表单组件：**
- `app/Filament/Forms/Components/SkuField.php`

**Blade 模板：**
- `resources/views/filament/forms/sku.blade.php`

## 注意事项

### 1. 数据迁移

- 如果已有生产数据，需要编写数据迁移逻辑
- 建议在测试环境先验证迁移逻辑

### 2. 缓存清理

- 清理相关缓存：`php artisan cache:clear`
- 清理配置缓存：`php artisan config:clear`

### 3. 前端缓存

- 清理浏览器缓存
- 重新编译前端资源：`php artisan filament:assets`

### 4. 回滚策略

- 如果需要回滚，需要重新创建旧表结构
- 建议保留旧表结构一段时间，确保新功能稳定后再删除

## 优势对比

### 简化前

- 5张表，结构复杂
- 需要维护属性和属性值
- 前端需要计算笛卡尔积
- 查询需要多表关联
- 获取 SKU 名称需要关联 attributes 和 attribute_values 表

### 简化后

- 2张表，结构简单
- 直接存储规格名称（name 字段）
- 前端逻辑简单（移除笛卡尔积计算）
- 查询简单高效（无需多表关联）
- 获取 SKU 名称直接从 skus 表读取

## 总结

通过简化SKU模式，可以：

1. 减少数据库表数量（5→2）
2. 简化前端逻辑（移除笛卡尔积计算）
3. 简化后端逻辑（移除属性管理）
4. 提高查询效率（减少关联查询）
5. 降低维护成本

**建议：** 在测试环境充分验证后再部署到生产环境。
