<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * 红包奖品经微信商家转账兑付：记录商户单号，队列重试时按单号复用，
     * 渠道按 out_bill_no 去重，避免重复打款（CAMP-06）。
     */
    public function up(): void
    {
        Schema::table('lottery_prize_records', static function (Blueprint $table) {
            $table->string('bill_no', 64)
                ->nullable()
                ->unique()
                ->comment('商户转账单号');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lottery_prize_records', static function (Blueprint $table) {
            $table->dropUnique(['bill_no']);
            $table->dropColumn('bill_no');
        });
    }
};
