<?php

use App\Enums\Finance\AccountAssetType;
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
            $table->comment('用户账户表（余额、积分）');
            $table->user()
                ->primary();
            $table->decimal('balance', 12)
                ->unsigned()
                ->default(0)
                ->comment('可用余额');
            $table->decimal('frozen_balance', 12)
                ->unsigned()
                ->default(0)
                ->comment('冻结余额');
            $table->decimal('points', 12)
                ->unsigned()
                ->default(0)
                ->comment('可用积分');
            $table->decimal('frozen_points', 12)
                ->unsigned()
                ->default(0)
                ->comment('冻结积分');
            $table->string('payment_password')
                ->nullable()
                ->comment('支付密码');
            $table->timestamps();
        });

        Schema::create('user_account_logs', static function (Blueprint $table) {
            $table->comment('用户账户变动日志');
            $table->id();
            $table->user();
            $table->string('type', 16)
                ->index()
                ->comment('变动类型');
            $table->string('asset', 16)
                ->index()
                ->default(AccountAssetType::Balance->value)
                ->comment('资产类型');
            $table->decimal('amount', 12)
                ->comment('变动金额/数值');
            $table->decimal('before', 12)
                ->unsigned()
                ->comment('变动前');
            $table->decimal('after', 12)
                ->unsigned()
                ->comment('变动后');
            $table->nullableMorphs('source');
            $table->string('remark')
                ->nullable()
                ->comment('备注');
            $table->jsonb('extra')
                ->nullable()
                ->comment('扩展信息');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('recharge_orders', static function (Blueprint $table) {
            $table->comment('充值订单表');
            $table->id();
            $table->tenant();
            $table->string('no', 32)
                ->unique()
                ->comment('充值单号');
            $table->user();
            $table->string('type', 32)
                ->index()
                ->comment('充值类型');
            $table->string('gateway', 32)
                ->index()
                ->comment('支付网关');
            $table->string('status', 32)
                ->index()
                ->comment('充值状态');
            $table->decimal('amount', 12)
                ->unsigned()
                ->comment('充值金额');
            $table->decimal('received_amount', 12)
                ->unsigned()
                ->comment('到账金额');
            $table->string('payment_no', 64)
                ->nullable()
                ->comment('第三方支付流水号');
            $table->timestamp('paid_at')
                ->nullable()
                ->comment('支付时间');
            $table->timestamp('completed_at')
                ->nullable()
                ->comment('完成时间');
            $table->timestamp('expired_at')
                ->nullable()
                ->comment('过期时间');
            $table->string('remark', 255)
                ->nullable()
                ->comment('备注');
            $table->ipAddress('ip')
                ->nullable()
                ->comment('支付IP');
            $table->text('user_agent')
                ->nullable()
                ->comment('支付设备');
            $table->timestamps();
            $table->softDeletes()
                ->index();
            $table->index(['tenant_id', 'status', 'created_at']);
            $table->index(['user_id', 'status', 'created_at']);
            $table->index('created_at');
        });

        Schema::create('withdraw_orders', static function (Blueprint $table) {
            $table->comment('提现订单表');
            $table->id();
            $table->string('no', 32)
                ->unique()
                ->comment('提现单号');
            $table->user();
            $table->decimal('amount', 12)
                ->unsigned()
                ->comment('提现金额');
            $table->decimal('fee', 12)
                ->unsigned()
                ->default(0)
                ->comment('手续费');
            $table->decimal('actual_amount', 12)
                ->unsigned()
                ->comment('实际到账金额');
            $table->string('gateway', 32)
                ->index()
                ->comment('提现方式');
            $table->string('status', 32)
                ->index()
                ->comment('提现状态');
            $table->jsonb('account_info')
                ->comment('收款账户信息');
            $table->string('remark', 255)
                ->nullable()
                ->comment('备注');
            $table->unsignedBigInteger('reviewer_id')
                ->nullable()
                ->comment('审核人');
            $table->timestamp('reviewed_at')
                ->nullable()
                ->comment('审核时间');
            $table->string('reject_reason', 255)
                ->nullable()
                ->comment('拒绝原因');
            $table->string('payment_no', 64)
                ->nullable()
                ->comment('打款流水号');
            $table->timestamp('paid_at')
                ->nullable()
                ->comment('打款时间');
            $table->ipAddress('ip')
                ->nullable()
                ->comment('IP地址');
            $table->text('user_agent')
                ->nullable()
                ->comment('设备信息');
            $table->timestamps();
            $table->softDeletes()
                ->index();
            $table->index(['user_id', 'status', 'created_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdraw_orders');
        Schema::dropIfExists('recharge_orders');
        Schema::dropIfExists('user_account_logs');
        Schema::dropIfExists('user_accounts');
    }
};
