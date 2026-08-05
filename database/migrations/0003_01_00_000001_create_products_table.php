<?php

use App\Enums\Mall\DeductStockType;
use App\Enums\Mall\ProductStatus;
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
            $table->comment('商品表');
            $table->id();
            $table->tenant();
            $table->unsignedBigInteger('supplier_id')
                ->index()
                ->nullable()
                ->comment('供应商');
            $table->string('name')
                ->comment('商品名称')
                ->fullText();
            $table->string('description')
                ->nullable()
                ->comment('商品简介')
                ->fullText();
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
            $table->unsignedBigInteger('delivery_id')
                ->nullable()
                ->index()
                ->comment('关联的运费模板ID');
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

            $table->index(['tenant_id', 'status', 'sort']);
            $table->index(['category_id', 'status']);
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
            $table->string('cover')
                ->nullable()
                ->comment('规格封面图');
            $table->decimal('origin_price', 12)
                ->unsigned()
                ->default(0)
                ->comment('原价格');
            $table->decimal('price', 12)
                ->unsigned()
                ->default(0)
                ->comment('销售价');
            $table->integer('stock')
                ->default(0)
                ->comment('库存');
            $table->integer('sale')
                ->default(0)
                ->comment('销量');
            $table->sort();
            $table->timestamps();
            $table->softDeletes();
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
