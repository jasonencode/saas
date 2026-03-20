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
        Schema::create('payment_orders', static function (Blueprint $table) {
            $table->id();
            $table->tenant();
            $table->string('no', 32)
                ->unique()
                ->comment('支付单号');
            $table->user();
            $table->nullableMorphs('paymentable');
            $table->string('gateway', 32)
                ->index()
                ->comment('支付网关');
            $table->string('status', 32)
                ->index()
                ->comment('支付状态');
            $table->decimal('amount')
                ->unsigned()
                ->unsigned()
                ->comment('支付金额');
            $table->timestamp('paid_at')
                ->nullable()
                ->comment('支付时间');
            $table->timestamp('expired_at')
                ->nullable()
                ->comment('过期时间');
            $table->ipAddress('ip')
                ->nullable()
                ->comment('支付IP');
            $table->text('user_agent')
                ->nullable()
                ->comment('支付设备');
            $table->timestamps();
            $table->softDeletes()
                ->index();
        });

        Schema::create('payment_refunds', static function (Blueprint $table) {
            $table->id();
            $table->tenant();
            $table->string('no', 32)
                ->unique()
                ->comment('退款单号');
            $table->foreignId('payment_order_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->decimal('amount')
                ->unsigned()
                ->comment('退款金额');
            $table->string('reason')
                ->nullable()
                ->comment('退款原因');
            $table->string('status', 32)
                ->index()
                ->comment('退款状态');
            $table->timestamp('refunded_at')
                ->nullable()
                ->comment('退款完成时间');
            $table->ipAddress('ip')
                ->nullable()
                ->comment('发起退款时的IP');
            $table->text('user_agent')
                ->nullable()
                ->comment('发起退款时的设备信息');
            $table->morphs('created_by'); // 创建人，可能是用户或后台管理员
            $table->unsignedBigInteger('approved_by')
                ->nullable()
                ->comment('审核人，只允许是后台用户');
            $table->timestamp('approved_at')
                ->nullable()
                ->comment('审核时间');
            $table->string('rejected_reason')
                ->nullable()
                ->comment('拒绝原因');
            $table->timestamps();
            $table->softDeletes()
                ->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_orders');
    }
};
