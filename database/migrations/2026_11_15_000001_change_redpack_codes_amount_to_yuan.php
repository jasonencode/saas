<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * redpack_codes.amount 原为 decimal(12,0)（注释写「分」），但全链路（表单、接口、
     * SendRedpackJob 的 bcmul 换算）都按「元」存取：PostgreSQL 下小数被四舍五入为
     * 整数（0.29 → 0），用户少收。统一改为 decimal(12,2) 元并修正注释（CAMP-07）。
     */
    public function up(): void
    {
        Schema::table('redpack_codes', static function (Blueprint $table) {
            $table->decimal('amount', 12, 2)
                ->unsigned()
                ->comment('该码对应的红包金额（元）')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('redpack_codes', static function (Blueprint $table) {
            $table->decimal('amount', 12)
                ->unsigned()
                ->comment('该码对应的红包金额（分）')
                ->change();
        });
    }
};
