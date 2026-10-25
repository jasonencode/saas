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
        Schema::create('event_logs', static function (Blueprint $table) {
            $table->comment('日志中心');
            $table->id();
            $table->string('event', 64)
                ->index()
                ->comment('事件标识（点号分层，如 order.paid）');
            $table->string('actor_type', 64)
                ->nullable()
                ->comment('操作者类型');
            $table->unsignedBigInteger('actor_id')
                ->nullable()
                ->comment('操作者ID');
            $table->index(['actor_type', 'actor_id']);
            $table->string('subject_type', 64)
                ->nullable()
                ->comment('业务对象类型');
            $table->unsignedBigInteger('subject_id')
                ->nullable()
                ->comment('业务对象ID');
            $table->index(['subject_type', 'subject_id']);
            $table->string('action', 64)
                ->comment('操作');
            $table->string('description')
                ->nullable()
                ->comment('说明');
            $table->jsonb('payload')
                ->nullable()
                ->comment('上下文/变更内容');
            $table->ipAddress('ip')
                ->nullable()
                ->comment('来源 IP');
            $table->string('user_agent', 512)
                ->nullable()
                ->comment('User-Agent');
            $table->string('request_id', 64)
                ->nullable()
                ->index()
                ->comment('请求追踪 ID');
            $table->timestamp('created_at')
                ->index()
                ->comment('发生时间');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_logs');
    }
};
