<?php

use App\Enums\AssetType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_accounts', static function (Blueprint $table) {
            $table->user()
                ->primary();
            $table->decimal('balance', 12)
                ->default(0)
                ->comment('可用余额');
            $table->decimal('frozen_balance', 12)
                ->default(0)
                ->comment('冻结余额');
            $table->decimal('points', 12)
                ->default(0)
                ->comment('可用积分');
            $table->decimal('frozen_points', 12)
                ->default(0)
                ->comment('冻结积分');
            $table->timestamps();
        });

        Schema::create('user_account_logs', static function (Blueprint $table) {
            $table->id();
            $table->user();
            $table->string('type', 16)
                ->index()
                ->comment('变动类型');
            $table->string('asset', 16)
                ->index()
                ->default(AssetType::Balance->value)
                ->comment('资产类型');
            $table->decimal('amount', 12)
                ->comment('变动金额/数值');
            $table->decimal('before', 12)
                ->comment('变动前');
            $table->decimal('after', 12)
                ->comment('变动后');
            $table->nullableMorphs('source');
            $table->string('remark')
                ->nullable()
                ->comment('备注');
            $table->json('extra')
                ->nullable()
                ->comment('扩展信息');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_account_logs');
        Schema::dropIfExists('user_accounts');
    }
};
