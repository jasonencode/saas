<?php

use App\Enums\RefundStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('refunds', static function (Blueprint $table) {
            $table->id();
            $table->tenant();
            $table->string('no', 32)
                ->index()
                ->comment('退款单号');
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete()
                ->comment('用户ID');
            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnDelete()
                ->comment('订单ID');
            $table->decimal('total', 20)
                ->default(0)
                ->comment('总退款金额');
            $table->string('status', 16)
                ->index()
                ->default(RefundStatus::Pending->value);
            $table->timestamp('refund_at')
                ->nullable()
                ->comment('退款时间');
            $table->timestamps();
            $table->softDeletes()
                ->index();

            $table->index(['created_at']);
        });

        Schema::create('refund_items', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('refund_id')
                ->constrained()
                ->cascadeOnDelete()
                ->comment('退款单ID');
            $table->foreignId('order_item_id')
                ->constrained('order_items')
                ->cascadeOnDelete()
                ->comment('订单详情ID');
            $table->unsignedInteger('qty')
                ->comment('数量');
            $table->decimal('price', 20)
                ->unsigned()
                ->comment('单价');
            $table->string('remark')
                ->nullable()
                ->comment('退款说明');
        });

        Schema::create('refund_logs', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('refund_id')
                ->constrained()
                ->cascadeOnDelete()
                ->comment('退款单ID');
            $table->morphs('user');
            $table->jsonb('context')
                ->nullable()
                ->comment('日志内容');
            $table->timestamp('created_at');
        });

        Schema::create('refund_expresses', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('refund_id')
                ->constrained()
                ->cascadeOnDelete()
                ->comment('退款单ID');
            $table->foreignId('express_id')
                ->nullable()
                ->constrained('expresses')
                ->comment('物流公司ID');
            $table->string('express_no', 32)
                ->nullable()
                ->comment('物流单号');
            $table->timestamps();

            $table->comment('退货物流');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_expresses');
        Schema::dropIfExists('refund_logs');
        Schema::dropIfExists('refund_items');
        Schema::dropIfExists('refunds');
    }
};
