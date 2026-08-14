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
            $table->string('fulfillment_type', 16)
                ->index()
                ->comment('订单履约方式: mail=快递邮寄, pickup=门店自提, virtual=虚拟商品');
            $table->string('pickup_code', 32)
                ->nullable()
                ->unique()
                ->comment('自提核销码（仅门店自提订单）');
            $table->unsignedBigInteger('pickup_point_id')
                ->nullable()
                ->index()
                ->comment('自提点ID（仅门店自提订单）');
            $table->timestamp('verified_at')
                ->nullable()
                ->comment('核销时间');
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

        Schema::create('pickup_points', static function (Blueprint $table) {
            $table->comment('自提点/门店');
            $table->id();
            $table->tenant();
            $table->string('name')
                ->comment('自提点名称');
            $table->string('contact')
                ->nullable()
                ->comment('联系人');
            $table->string('phone', 32)
                ->nullable()
                ->comment('联系电话');
            $table->regionAddress();
            $table->string('remark')
                ->nullable()
                ->comment('备注');
            $table->easyStatus();
            $table->sort();
            $table->timestamps();
            $table->softDeletes()
                ->index();
            $table->index(['tenant_id', 'status', 'sort']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pickup_points');
        Schema::dropIfExists('order_addresses');
        Schema::dropIfExists('order_shippings');
        Schema::dropIfExists('order_logs');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
