<?php

use App\Enums\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mall_orders', static function (Blueprint $table) {
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
            $table->enum('status', OrderStatus::values())
                ->default(OrderStatus::Pending->value)
                ->comment('订单状态');
            $table->timestamps();
            $table->softDeletes()
                ->index();

            $table->index(['created_at']);
        });

        Schema::create('mall_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')
                ->index();
            $table->unsignedBigInteger('product_id')
                ->index();
            $table->unsignedBigInteger('sku_id')
                ->index();
            $table->unsignedInteger('qty')
                ->comment('购买数量');
            $table->decimal('price', 20)
                ->comment('商品单价');
            $table->string('remark')
                ->nullable()
                ->comment('商品备注');
        });

        Schema::create('mall_order_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')
                ->index();
            $table->morphs('user');
            $table->jsonb('context')
                ->nullable();
            $table->timestamp('created_at');
        });

        Schema::create('mall_order_expresses', function (Blueprint $table) {
            $table->comment('发货记录');
            $table->id();
            $table->unsignedBigInteger('order_id')
                ->index();
            $table->unsignedBigInteger('express_id')
                ->index();
            $table->string('express_no', 32);
            $table->timestamp('delivery_at')
                ->nullable();
            $table->timestamp('sign_at')
                ->nullable();
            $table->timestamps();
        });

        Schema::create('mall_order_addresses', function (Blueprint $table) {
            $table->comment('收货地址');
            $table->id();
            $table->unsignedBigInteger('order_id')
                ->index();
            $table->unsignedBigInteger('address_id')
                ->index();
            $table->string('name', 32);
            $table->string('mobile', 32);
            $table->unsignedInteger('province_id')
                ->index();
            $table->unsignedInteger('city_id')
                ->index();
            $table->unsignedInteger('district_id')
                ->index();
            $table->string('address');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mall_order_addresses');
        Schema::dropIfExists('mall_order_expresses');
        Schema::dropIfExists('mall_order_logs');
        Schema::dropIfExists('mall_order_items');
        Schema::dropIfExists('mall_orders');
    }
};
