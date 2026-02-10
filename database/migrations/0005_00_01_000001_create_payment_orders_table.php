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
            $table->string('no', 32)
                ->unique()
                ->comment('支付单号');
            $table->user();
            $table->nullableMorphs('payable');
            $table->string('gateway', 32)
                ->index()
                ->comment('支付网关');
            $table->string('status', 32)
                ->index()
                ->comment('支付状态');
            $table->decimal('amount', 12, 2)
                ->comment('支付金额');
            $table->string('transaction_id')
                ->nullable()
                ->index()
                ->comment('第三方交易号');
            $table->timestamp('paid_at')
                ->nullable()
                ->comment('支付时间');
            $table->timestamp('expired_at')
                ->nullable()
                ->comment('过期时间');
            $table->jsonb('extra')
                ->nullable()
                ->comment('扩展信息');
            $table->string('remark')
                ->nullable()
                ->comment('备注');
            $table->string('ip', 64)
                ->nullable()
                ->comment('支付IP');
            $table->text('device')
                ->nullable()
                ->comment('支付设备');
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
