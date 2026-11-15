<?php

use App\Enums\Mall\RefundStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 收敛历史「已签收」退款状态
 *
 * Received 已退出新流程（签收后直接进入退款处理中），但历史数据仍占用商品可退数量。
 * 统一迁移为 Processing，交给后台「发起退款」或「确认退款」继续推进。
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('refunds')
            ->where('status', RefundStatus::Received->value)
            ->update(['status' => RefundStatus::Processing->value]);
    }

    /**
     * Reverse the migrations.
     *
     * 无法区分原本就是 Processing 的单据，回滚不做处理。
     */
    public function down(): void {}
};
