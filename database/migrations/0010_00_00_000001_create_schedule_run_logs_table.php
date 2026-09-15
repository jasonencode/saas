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
        Schema::create('schedule_run_logs', static function (Blueprint $table) {
            $table->comment('计划任务执行日志');
            $table->id();
            $table->string('task', 64)
                ->comment('任务标识（命令签名）');
            $table->string('label', 64)
                ->nullable()
                ->comment('任务中文名称');
            $table->string('expression', 32)
                ->nullable()
                ->comment('cron 表达式');
            $table->string('server', 64)
                ->nullable()
                ->comment('执行节点');
            $table->string('status', 16)
                ->comment('执行状态');
            $table->string('source', 16)
                ->index()
                ->default('schedule')
                ->comment('触发来源：schedule 调度 / manual 手动执行');
            $table->timestamp('started_at')
                ->comment('开始时间');
            $table->timestamp('finished_at')
                ->nullable()
                ->comment('结束时间');
            $table->unsignedInteger('duration_ms')
                ->nullable()
                ->comment('耗时毫秒');
            $table->text('exception')
                ->nullable()
                ->comment('失败原因');
            $table->jsonb('context')
                ->nullable()
                ->comment('业务上下文');
            $table->text('output')
                ->nullable()
                ->comment('输出摘要');
            $table->timestamp('created_at')
                ->index()
                ->comment('创建时间');

            $table->index(['task', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_run_logs');
    }
};
