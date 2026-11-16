<?php

use App\Enums\Finance\PlanTrigger;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('plans', static function (Blueprint $table) {
            $table->string('trigger', 32)
                ->default(PlanTrigger::Manual->value)
                ->index()
                ->after('alias')
                ->comment('触发方式:枚举值');
        });

        // 同一计划对同一目标只允许一张凭据，防止事件重复触发导致重复结算
        Schema::table('vouchers', static function (Blueprint $table) {
            $table->unique(['plan_id', 'target_type', 'target_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', static function (Blueprint $table) {
            $table->dropIndex(['trigger']);
            $table->dropColumn('trigger');
        });

        Schema::table('vouchers', static function (Blueprint $table) {
            $table->dropUnique(['plan_id', 'target_type', 'target_id']);
        });
    }
};
