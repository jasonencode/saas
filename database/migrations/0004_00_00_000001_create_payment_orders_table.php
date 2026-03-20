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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_orders');
    }
};
