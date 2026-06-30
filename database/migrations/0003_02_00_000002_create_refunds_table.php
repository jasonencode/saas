<?php

use App\Enums\Mall\RefundStatus;
use App\Enums\Mall\RefundType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('refunds', static function (Blueprint $table) {
            $table->id();
            $table->tenant();
            $table->user();
            $table->string('no', 32)
                ->index()
                ->comment('退款单号');
            $table->unsignedBigInteger('order_id')
                ->index()
                ->comment('订单ID');
            $table->decimal('total', 12)
                ->unsigned()
                ->default(0)
                ->comment('总退款金额');
            $table->decimal('goods_amount', 12)
                ->unsigned()
                ->default(0)
                ->comment('退款商品金额');
            $table->decimal('freight_amount', 12)
                ->unsigned()
                ->default(0)
                ->comment('退款运费金额');
            $table->string('status', 16)
                ->index()
                ->default(RefundStatus::Pending->value);
            $table->string('type', 16)
                ->index()
                ->default(RefundType::OnlyRefund->value)
                ->comment('退款类型');
            $table->string('reason', 16)
                ->nullable()
                ->index()
                ->comment('退款原因');
            $table->string('reason_detail')
                ->nullable()
                ->comment('退款原因详情（当选择其他时）');
            $table->unsignedBigInteger('approved_by')
                ->nullable()
                ->index()
                ->comment('审核人ID');
            $table->timestamp('approved_at')
                ->nullable()
                ->comment('审核时间');
            $table->text('approval_remark')
                ->nullable()
                ->comment('审核备注');
            $table->timestamp('refund_at')
                ->nullable()
                ->comment('退款时间');
            $table->timestamps();
            $table->softDeletes()
                ->index();

            $table->index(['created_at']);
        });

        Schema::create('refund_items', static function (Blueprint $table) {
            $table->comment('退款商品明细表');
            $table->id();
            $table->unsignedBigInteger('refund_id')
                ->index()
                ->comment('退款单ID');
            $table->unsignedBigInteger('order_item_id')
                ->index()
                ->comment('订单详情ID');
            $table->unsignedInteger('qty')
                ->comment('数量');
            $table->decimal('price', 12)
                ->unsigned()
                ->comment('单价');
            $table->string('remark')
                ->nullable()
                ->comment('退款说明');
        });

        Schema::create('refund_logs', static function (Blueprint $table) {
            $table->comment('退款状态变更日志');
            $table->id();
            $table->unsignedBigInteger('refund_id')
                ->index()
                ->comment('退款单ID');
            $table->string('action', 32)
                ->index()
                ->comment('操作类型');
            $table->unsignedBigInteger('operator_id')
                ->nullable()
                ->index()
                ->comment('操作人ID');
            $table->text('remark')
                ->nullable()
                ->comment('操作备注');
            $table->jsonb('context')
                ->nullable()
                ->comment('日志内容');
            $table->timestamp('created_at')
                ->index();
        });

        Schema::create('refund_expresses', static function (Blueprint $table) {
            $table->comment('退货物流表');
            $table->id();
            $table->unsignedBigInteger('refund_id')
                ->index()
                ->comment('退款单ID');
            $table->unsignedBigInteger('express_id')
                ->index()
                ->comment('物流公司ID')
                ->nullable();
            $table->string('express_no', 32)
                ->nullable()
                ->comment('物流单号');
            $table->string('status', 16)
                ->index()
                ->default('pending')
                ->comment('物流状态');
            $table->timestamp('shipped_at')
                ->nullable()
                ->comment('发货时间');
            $table->timestamp('received_at')
                ->nullable()
                ->comment('签收时间');
            $table->timestamp('checked_at')
                ->nullable()
                ->comment('验收时间');
            $table->timestamps();
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refund_expresses');
        Schema::dropIfExists('refund_logs');
        Schema::dropIfExists('refund_items');
        Schema::dropIfExists('refunds');
    }
};
