<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\Mall\DeductStockType;
use App\Enums\Mall\ProductStatus;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mall_products', static function(Blueprint $table) {
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
                ->nullable()
                ->index()
                ->comment('品牌ID');
            $table->enum('deduct_stock_type', DeductStockType::values())
                ->default(DeductStockType::Paid->value)
                ->comment('库存扣减方式');
            $table->unsignedBigInteger('views')
                ->default(0)
                ->comment('浏览量');
            $table->enum('status', ProductStatus::values())
                ->default(ProductStatus::Pending->value)
                ->comment('商品状态');
            $table->boolean('can_cart')
                ->default(false)
                ->comment('是否可以加入购物车');
            $table->sort();
            $table->jsonb('materials')
                ->nullable();
            $table->jsonb('ext')
                ->nullable();
            $table->timestamps();
            $table->softDeletes()
                ->index();

            $table->index(['created_at']);
        });

        Schema::create('mall_product_category', static function(Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')
                ->index();
            $table->unsignedBigInteger('category_id')
                ->index();
            $table->timestamps();
        });

        Schema::create('mall_skus', static function(Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('mall_products')
                ->onDelete('cascade');
            $table->cover();
            $table->decimal('origin_price', 20)
                ->unsigned()
                ->default(0)
                ->comment('原价格');
            $table->decimal('price', 20)
                ->unsigned()
                ->default(0)
                ->comment('销售价');
            $table->integer('stocks')
                ->default(0)
                ->comment('库存');
            $table->integer('sales')
                ->default(0)
                ->comment('销量');
            $table->string('code')
                ->index()
                ->nullable()
                ->comment('商品编号，一般为69码');
            $table->timestamps();
        });

        Schema::create('mall_attributes', static function(Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('mall_products')
                ->onDelete('cascade');
            $table->string('name')
                ->comment('规格名称');
            $table->timestamps();
        });

        Schema::create('mall_attribute_values', static function(Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')
                ->constrained('mall_attributes')
                ->onDelete('cascade');
            $table->string('value')
                ->comment('属性值');
            $table->timestamps();
        });

        Schema::create('mall_sku_attribute', static function(Blueprint $table) {
            $table->id();
            $table->foreignId('sku_id')
                ->constrained('mall_skus')
                ->onDelete('cascade');
            $table->foreignId('attribute_id')
                ->constrained('mall_attributes')
                ->onDelete('cascade');
            $table->foreignId('attribute_value_id')
                ->constrained('mall_attribute_values')
                ->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('mall_product_logs', function(Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')
                ->index();
            $table->string('user_type')
                ->nullable();
            $table->unsignedBigInteger('user_id')
                ->nullable();
            $table->jsonb('records')
                ->nullable();
            $table->timestamp('created_at');

            $table->index(['user_type', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mall_product_logs');
        Schema::dropIfExists('mall_sku_attribute');
        Schema::dropIfExists('mall_attribute_values');
        Schema::dropIfExists('mall_attributes');
        Schema::dropIfExists('mall_skus');
        Schema::dropIfExists('mall_product_category');
        Schema::dropIfExists('mall_products');
    }
};
