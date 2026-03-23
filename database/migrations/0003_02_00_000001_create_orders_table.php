<?php

use App\Enums\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', static function (Blueprint $table) {
            $table->id();
            $table->tenant();
            $table->no();
            $table->user();
            $table->decimal('amount')
                ->unsigned()
                ->default(0)
                ->comment('订单金额');
            $table->decimal('freight')
                ->unsigned()
                ->default(0)
                ->comment('物流费用');
            $table->timestamp('expired_at')
                ->nullable()
                ->comment('订单过期时间');
            $table->timestamp('paid_at')
                ->nullable()
                ->comment('支付时间');
            $table->string('status', 16)
                ->index()
                ->default(OrderStatus::Pending->value)
                ->comment('订单状态');
            $table->timestamps();
            $table->softDeletes()
                ->index();

            $table->index(['created_at']);
        });

        Schema::create('order_items', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')
                ->index()
                ->comment('订单ID')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('product_id')
                ->index()
                ->comment('商品ID')
                ->constrained('products')
                ->cascadeOnDelete();
            $table->foreignId('sku_id')
                ->index()
                ->comment('SKU ID')
                ->constrained()
                ->cascadeOnDelete();
            $table->unsignedInteger('qty')
                ->comment('购买数量');
            $table->decimal('price')
                ->unsigned()
                ->comment('商品单价');
            $table->string('remark')
                ->nullable()
                ->comment('商品备注');
        });

        Schema::create('order_logs', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')
                ->index()
                ->comment('订单ID')
                ->constrained()
                ->cascadeOnDelete();
            $table->morphs('user');
            $table->jsonb('context')
                ->nullable()
                ->comment('日志内容');
            $table->timestamp('created_at');
        });

        Schema::create('order_expresses', static function (Blueprint $table) {
            $table->comment('发货记录');
            $table->id();
            $table->foreignId('order_id')
                ->index()
                ->comment('订单ID')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('express_id')
                ->index()
                ->comment('物流公司ID')
                ->constrained('expresses')
                ->cascadeOnDelete();
            $table->string('express_no', 32)
                ->comment('物流单号');
            $table->timestamp('delivery_at')
                ->nullable()
                ->comment('发货时间');
            $table->timestamp('sign_at')
                ->nullable()
                ->comment('签收时间');
            $table->timestamps();
        });

        Schema::create('order_addresses', static function (Blueprint $table) {
            $table->comment('收货地址');
            $table->id();
            $table->foreignId('order_id')
                ->index()
                ->comment('订单ID')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('address_id')
                ->nullable()
                ->index()
                ->comment('地址ID')
                ->constrained('addresses')
                ->nullOnDelete();
            $table->string('name', 32)
                ->comment('收货人姓名');
            $table->string('mobile', 32)
                ->comment('收货人手机');
            $table->regionAddress();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_addresses');
        Schema::dropIfExists('order_expresses');
        Schema::dropIfExists('order_logs');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
