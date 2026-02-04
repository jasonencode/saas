<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\Mall\RefundStatus;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mall_refunds', function(Blueprint $table) {
            $table->id();
            $table->tenant();
            $table->string('no', 32)
                ->index()
                ->comment('退款单号');
            $table->unsignedBigInteger('user_id')
                ->index();
            $table->unsignedBigInteger('order_id')
                ->index();
            $table->decimal('total', 20)
                ->default(0)
                ->comment('总退款金额');
            $table->enum('status', RefundStatus::values())
                ->default(RefundStatus::Pending->value);
            $table->timestamp('refund_at')
                ->nullable()
                ->comment('退款时间');
            $table->timestamps();
            $table->softDeletes()
                ->index();

            $table->index(['created_at']);
        });

        Schema::create('mall_refund_items', function(Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('refund_id')
                ->index();
            $table->unsignedBigInteger('order_item_id')
                ->comment('详情ID');
            $table->unsignedInteger('qty')
                ->comment('数量');
            $table->decimal('price', 20)
                ->unsigned()
                ->comment('单价');
            $table->string('remark')
                ->nullable()
                ->comment('退款说明');
        });

        Schema::create('mall_refund_logs', function(Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('refund_id')
                ->index();
            $table->morphs('user');
            $table->jsonb('context')
                ->nullable();
            $table->timestamp('created_at');
        });

        Schema::create('mall_refund_expresses', function(Blueprint $table) {
            $table->id();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mall_refund_expresses');
        Schema::dropIfExists('mall_refund_logs');
        Schema::dropIfExists('mall_refund_items');
        Schema::dropIfExists('mall_refunds');
    }
};
