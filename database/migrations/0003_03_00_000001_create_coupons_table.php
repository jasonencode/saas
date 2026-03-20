<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('coupons', static function (Blueprint $table) {
            $table->id();
            $table->tenant();
            $table->string('name')
                ->comment('优惠券名称');
            $table->string('code', 64)
                ->unique()
                ->comment('优惠券代码，唯一');
            $table->string('description')
                ->nullable();
            $table->string('type', 64)
                ->index()
                ->comment('优惠券类型');
            $table->decimal('value')
                ->unsigned()
                ->comment('折扣值');
            $table->decimal('min_amount')
                ->unsigned()
                ->nullable()
                ->comment('最低消费金额，可选');
            $table->decimal('max_discount')
                ->unsigned()
                ->nullable()
                ->comment('最大折扣金额（仅对百分比有效），可选');
            $table->integer('usage_limit')
                ->nullable()
                ->comment('使用次数限制，可选');
            $table->integer('usage_limit_per_user')
                ->nullable()
                ->comment('每人使用次数限制，可选');
            $table->string('expired_type', 64)
                ->index()
                ->comment('过期类型');
            $table->integer('days')
                ->default(0)
                ->comment('有效期（天），为0永不过期');
            $table->dateTime('start_at')
                ->nullable()
                ->comment('优惠券开始日期');
            $table->dateTime('end_at')
                ->nullable()
                ->comment('优惠券结束日期');
            $table->easyStatus();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('coupon_product', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')
                ->index()
                ->comment('优惠券ID')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('product_id')
                ->index()
                ->comment('商品ID')
                ->constrained('products')
                ->cascadeOnDelete();
            $table->timestamps();
        });


        Schema::create('coupon_user', static function (Blueprint $table) {
            $table->id();
            $table->user();
            $table->foreignId('coupon_id')
                ->index()
                ->comment('优惠券ID')
                ->constrained()
                ->cascadeOnDelete();
            $table->dateTime('expired_at')
                ->nullable()
                ->comment('过期时间');
            $table->boolean('is_used')
                ->default(false)
                ->comment('是否已使用');
            $table->timestamp('used_at')
                ->nullable()
                ->comment('使用时间');
            $table->timestamps();
        });

        Schema::create('coupon_order', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')
                ->index()
                ->comment('订单ID')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('coupon_id')
                ->index()
                ->comment('优惠券ID')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('coupon_user_id')
                ->index()
                ->comment('用户优惠券记录ID')
                ->constrained('coupon_user')
                ->cascadeOnDelete();
            $table->decimal('discount_amount')
                ->unsigned()
                ->default(0)
                ->comment('抵扣金额');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupon_order');
        Schema::dropIfExists('coupon_user');
        Schema::dropIfExists('coupon_product');
        Schema::dropIfExists('coupons');
    }
};
