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
        Schema::create('lotteries', static function (Blueprint $table) {
            $table->comment('抽奖活动');
            $table->id();
            $table->tenant();
            $table->string('name')
                ->comment('活动名称');
            $table->text('description')
                ->nullable()
                ->comment('活动描述');
            $table->string('cover')
                ->nullable()
                ->comment('活动封面图');

            // 抽奖模式配置
            $table->string('draw_mode', 64)
                ->default('free')
                ->comment('free:免费抽奖, points:积分抽奖');
            $table->unsignedInteger('free_draws_per_day')
                ->default(0)
                ->comment('每日免费抽奖次数');
            $table->decimal('points_per_draw', 12)
                ->unsigned()
                ->default(0)
                ->comment('每次抽奖消耗积分');
            $table->unsignedInteger('max_draws_per_user')
                ->nullable()
                ->comment('每人总抽奖次数上限(null=不限)');

            // 活动时间
            $table->timestamp('start_at')
                ->nullable()
                ->comment('活动开始时间');
            $table->timestamp('end_at')
                ->nullable()
                ->comment('活动结束时间');

            $table->easyStatus();
            $table->sort();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('lottery_prizes', static function (Blueprint $table) {
            $table->comment('抽奖奖品');
            $table->id();
            $table->unsignedBigInteger('lottery_id')
                ->index()
                ->comment('所属活动');
            $table->string('name')
                ->comment('奖品名称');
            $table->string('type', 64)
                ->index()
                ->comment('balance:余额, points:积分, coupon:优惠券, redpack:红包, physical:实物, none:谢谢参与');
            $table->string('cover')
                ->nullable()
                ->comment('奖品图片');
            $table->json('prize_config')
                ->nullable()
                ->comment('奖品配置 JSON');
            $table->unsignedInteger('weight')
                ->default(100)
                ->comment('权重(越大概率越高)');
            $table->unsignedInteger('total_quantity')
                ->default(0)
                ->comment('总数量(0=无限)');
            $table->unsignedInteger('remaining_quantity')
                ->default(0)
                ->comment('剩余数量');
            $table->unsignedInteger('user_limit')
                ->nullable()
                ->comment('每人限领数量(null=不限)');
            $table->sort();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('lottery_draws', static function (Blueprint $table) {
            $table->comment('抽奖记录');
            $table->id();
            $table->unsignedBigInteger('lottery_id')
                ->index()
                ->comment('所属活动');
            $table->unsignedBigInteger('user_id')
                ->index()
                ->comment('所属用户');
            $table->unsignedBigInteger('lottery_prize_id')
                ->nullable()
                ->comment('中奖奖品ID(null=谢谢参与)');
            $table->string('draw_cost_type', 16)
                ->default('free')
                ->comment('free:免费, points:积分');
            $table->decimal('draw_cost_amount', 12)
                ->unsigned()
                ->default(0)
                ->comment('消耗数量');
            $table->ipAddress('ip_address')
                ->nullable();
            $table->text('user_agent')
                ->nullable();
            $table->timestamp('created_at')
                ->nullable();

            $table->index(['lottery_id', 'user_id', 'created_at']);
        });

        Schema::create('lottery_prize_records', static function (Blueprint $table) {
            $table->comment('奖品发放记录');
            $table->id();
            $table->unsignedBigInteger('lottery_draw_id')
                ->index()
                ->comment('关联抽奖记录');
            $table->unsignedBigInteger('lottery_id')
                ->index()
                ->comment('所属活动');
            $table->unsignedBigInteger('user_id')
                ->index()
                ->comment('所属用户');
            $table->unsignedBigInteger('lottery_prize_id')
                ->index()
                ->comment('关联奖品');
            $table->string('type', 64)
                ->index()
                ->comment('balance:余额, points:积分, coupon:优惠券, redpack:红包, physical:实物');
            $table->json('prize_detail')
                ->nullable()
                ->comment('中奖时奖品配置快照');
            $table->string('status', 16)
                ->default('pending')
                ->comment('pending:待兑奖, fulfilled:已兑奖, expired:已过期, cancelled:已取消');
            $table->text('fulfillment_note')
                ->nullable()
                ->comment('兑奖备注');
            $table->timestamp('fulfilled_at')
                ->nullable()
                ->comment('兑奖时间');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lottery_prize_records');
        Schema::dropIfExists('lottery_draws');
        Schema::dropIfExists('lottery_prizes');
        Schema::dropIfExists('lotteries');
    }
};
