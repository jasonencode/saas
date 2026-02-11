<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vouchers', static function (Blueprint $table) {
            $table->comment('凭据表');
            $table->id();
            $table->string('no', 64)
                ->unique()
                ->comment('凭据单号');
            $table->unsignedBigInteger('plan_id')
                ->index()
                ->comment('单据要执行的计划');
            $table->unsignedBigInteger('user_id')
                ->index()
                ->comment('发起人、起点');
            $table->morphs('target');
            $table->string('status', 32)
                ->index()
                ->nullable();
            $table->longText('exception')
                ->nullable();
            $table->timestamp('completed_at')
                ->nullable()
                ->comment('完成时间');
            $table->timestamp('scheduled_at')
                ->nullable()
                ->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('voucher_logs', static function(Blueprint $table) {
            $table->comment('凭据日志');
            $table->id();
            $table->unsignedBigInteger('voucher_id')
                ->index()
                ->comment('凭据ID');
            $table->unsignedBigInteger('task_id')
                ->nullable()
                ->index()
                ->comment('任务ID');
            $table->string('step')
                ->nullable()
                ->comment('步骤');
            $table->string('status', 32)
                ->index()
                ->comment('状态');
            $table->text('message')
                ->nullable()
                ->comment('消息');
            $table->json('meta')
                ->nullable()
                ->comment('元数据');
            $table->unsignedInteger('duration_ms')
                ->nullable()
                ->comment('执行时间（毫秒）');
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_logs');
        Schema::dropIfExists('vouchers');
    }
};
