<?php

use App\Enums\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', static function (Blueprint $table) {
            $table->id();
            $table->tenant();
            $table->no();
            $table->user();
            $table->decimal('amount', 20)
                ->default(0)
                ->comment('订单金额');
            $table->decimal('freight')
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
                ->constrained()
                ->cascadeOnDelete()
                ->comment('订单ID');
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete()
                ->comment('商品ID');
            $table->foreignId('sku_id')
                ->constrained()
                ->cascadeOnDelete()
                ->comment('SKU ID');
            $table->unsignedInteger('qty')
                ->comment('购买数量');
            $table->decimal('price', 20)
                ->comment('商品单价');
            $table->string('remark')
                ->nullable()
                ->comment('商品备注');
        });

        Schema::create('order_logs', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnDelete()
                ->comment('订单ID');
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
                ->constrained()
                ->cascadeOnDelete()
                ->comment('订单ID');
            $table->foreignId('express_id')
                ->constrained('expresses')
                ->cascadeOnDelete()
                ->comment('物流公司ID');
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
                ->constrained()
                ->cascadeOnDelete()
                ->comment('订单ID');
            $table->foreignId('address_id')
                ->nullable()
                ->constrained('addresses')
                ->nullOnDelete()
                ->comment('地址ID');
            $table->string('name', 32)
                ->comment('收货人姓名');
            $table->string('mobile', 32)
                ->comment('收货人手机');
            $table->unsignedInteger('province_id')
                ->index()
                ->comment('省份ID');
            $table->unsignedInteger('city_id')
                ->index()
                ->comment('城市ID');
            $table->unsignedInteger('district_id')
                ->index()
                ->comment('区县ID');
            $table->string('address')
                ->comment('详细地址');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_addresses');
        Schema::dropIfExists('order_expresses');
        Schema::dropIfExists('order_logs');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
