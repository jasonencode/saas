<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('carts', static function (Blueprint $table) {
            $table->id();
            $table->tenant();
            $table->user();
            $table->string('session_id', 255)
                ->nullable()
                ->comment('未登录用户的会话标识');
            $table->easyStatus();
            $table->timestamps();
            $table->softDeletes();

            // 唯一索引：同一租户下每个登录用户只有一个购物车
            $table->unique(['tenant_id', 'user_id']);
            // 唯一索引：同一租户下每个会话（未登录）只有一个购物车
            $table->unique(['tenant_id', 'session_id']);
            // 辅助索引
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('cart_items', static function (Blueprint $table) {
            $table->id();
            $table->tenant();
            $table->foreignId('cart_id')
                ->index()
                ->constrained()
                ->onDelete('cascade');
            $table->unsignedBigInteger('product_id')
                ->index();
            $table->unsignedBigInteger('sku_id')
                ->index();
            $table->unsignedInteger('qty')
                ->default(1);
            $table->decimal('price_at_add', 10)
                ->unsigned()
                ->comment('加入购物车时的单价快照');
            $table->boolean('selected')
                ->default(true)
                ->comment('是否被选中');
            $table->timestamps();

            // 唯一索引：同一个购物车内同一 SKU 只能有一条记录
            $table->unique(['cart_id', 'sku_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
    }
};
