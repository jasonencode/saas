<?php

use App\Enums\DeductStockType;
use App\Enums\ProductStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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

        Schema::create('product_category', static function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')
                ->index()
                ->comment('商品ID');
            $table->unsignedBigInteger('category_id')
                ->index()
                ->comment('分类ID');
            $table->timestamps();

            $table->primary(['product_id', 'category_id']);
            $table->comment('商品与分类关系');
        });

        Schema::create('skus', static function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')
                ->index()
                ->comment('商品ID');
            $table->cover();
            $table->decimal('origin_price')
                ->unsigned()
                ->default(0)
                ->comment('原价格');
            $table->decimal('price')
                ->unsigned()
                ->default(0)
                ->comment('销售价');
            $table->integer('stock')
                ->default(0)
                ->comment('库存');
            $table->integer('sale')
                ->default(0)
                ->comment('销量');
            $table->string('code')
                ->index()
                ->nullable()
                ->comment('商品编号，一般为69码');
            $table->timestamps();
        });

        Schema::create('attributes', static function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')
                ->index()
                ->comment('商品ID');
            $table->string('name')
                ->comment('规格名称');
            $table->timestamps();
        });

        Schema::create('attribute_values', static function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attribute_id')
                ->index()
                ->comment('属性ID');
            $table->string('value')
                ->comment('属性值');
            $table->timestamps();
        });

        Schema::create('sku_attribute', static function (Blueprint $table) {
            $table->unsignedBigInteger('sku_id')
                ->index()
                ->comment('SKU ID');
            $table->unsignedBigInteger('attribute_id')
                ->index()
                ->comment('属性ID');
            $table->unsignedBigInteger('attribute_value_id')
                ->index()
                ->comment('属性值ID');
            $table->timestamps();

            $table->primary(['sku_id', 'attribute_id', 'attribute_value_id']);
        });

        Schema::create('product_logs', static function (Blueprint $table) {
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
        Schema::dropIfExists('sku_attribute');
        Schema::dropIfExists('attribute_values');
        Schema::dropIfExists('attributes');
        Schema::dropIfExists('skus');
        Schema::dropIfExists('product_category');
        Schema::dropIfExists('products');
    }
};
