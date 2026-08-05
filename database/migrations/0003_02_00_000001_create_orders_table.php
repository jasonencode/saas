<?php

use App\Enums\Mall\OrderStatus;
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
            $table->comment('订单主表');
            $table->id();
            $table->tenant();
            $table->user();
            $table->no();
            $table->decimal('amount', 12)
                ->unsigned()
                ->default(0)
                ->comment('订单金额');
            $table->decimal('freight', 12)
                ->unsigned()
                ->default(0)
                ->comment('物流费用');
            $table->timestamp('expired_at')
                ->index()
                ->nullable()
                ->comment('订单过期时间');
            $table->timestamp('paid_at')
                ->index()
                ->nullable()
                ->comment('支付时间');
            $table->timestamp('signed_at')
                ->nullable()
                ->comment('签收时间');
            $table->string('status', 16)
                ->index()
                ->default(OrderStatus::Pending->value)
                ->comment('订单状态');
            $table->string('remark')
                ->nullable()
                ->fullText()
                ->comment('买家备注');
            $table->string('seller_remark')
                ->nullable()
                ->fullText()
                ->comment('商家备注');
            $table->timestamps();
            $table->softDeletes()
                ->index();

            $table->index(['tenant_id', 'status', 'created_at']);
            $table->index(['user_id', 'status', 'created_at']);
            $table->index('created_at');
        });

        Schema::create('order_items', static function (Blueprint $table) {
            $table->comment('订单商品详情表');
            $table->id();
            $table->unsignedBigInteger('order_id')
                ->index()
                ->comment('订单ID');
            $table->unsignedBigInteger('order_shipping_id')
                ->nullable()
                ->index()
                ->comment('物流ID');
            $table->string('orderable_type', 255)
                ->nullable()
                ->index()
                ->comment('可订购主体类型（多态）');
            $table->unsignedBigInteger('orderable_id')
                ->nullable()
                ->index()
                ->comment('可订购主体ID（多态）');
            $table->string('orderable_name')
                ->nullable()
                ->fullText()
                ->comment('商品名称快照');
            $table->unsignedInteger('qty')
                ->comment('购买数量');
            $table->decimal('price', 12)
                ->unsigned()
                ->comment('商品单价');
            $table->string('remark')
                ->nullable()
                ->fullText()
                ->comment('商品备注');
        });

        Schema::create('order_logs', static function (Blueprint $table) {
            $table->comment('订单状态变更日志');
            $table->id();
            $table->unsignedBigInteger('order_id')
                ->index()
                ->comment('订单ID');
            $table->string('action', 32)
                ->index()
                ->nullable()
                ->comment('操作类型');
            $table->string('remark')
                ->nullable()
                ->fullText()
                ->comment('操作备注');
            $table->jsonb('context')
                ->nullable()
                ->comment('日志内容');
            $table->nullableMorphs('operator');
            $table->timestamp('created_at')
                ->index();
        });

        Schema::create('order_shippings', static function (Blueprint $table) {
            $table->comment('发货记录');
            $table->id();
            $table->unsignedBigInteger('order_id')
                ->index()
                ->comment('订单ID');
            $table->unsignedBigInteger('express_id')
                ->nullable()
                ->index()
                ->comment('物流公司ID');
            $table->string('express_no', 32)
                ->comment('物流单号');
            $table->string('name', 32)->nullable()->comment('收货人姓名');
            $table->string('mobile', 32)->nullable()->comment('收货人手机');
            $table->regionAddress();
            $table->timestamp('delivery_at')
                ->nullable()
                ->comment('发货时间');
            $table->timestamp('sign_at')
                ->nullable()
                ->comment('签收时间');
            $table->timestamps();
            $table->index(['order_id', 'created_at']);
            $table->index('created_at');
            $table->softDeletes()
                ->index();
        });

        Schema::create('order_addresses', static function (Blueprint $table) {
            $table->comment('收货地址');
            $table->id();
            $table->unsignedBigInteger('order_id')
                ->index()
                ->comment('订单ID');
            $table->unsignedBigInteger('address_id')
                ->nullable()
                ->index()
                ->comment('地址ID');
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
        Schema::dropIfExists('order_shippings');
        Schema::dropIfExists('order_logs');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
