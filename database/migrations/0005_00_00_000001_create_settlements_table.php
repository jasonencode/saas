<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plans', static function (Blueprint $table) {
            $table->comment('结算计划');
            $table->id();
            $table->tenant();
            $table->string('name')
                ->comment('计划名称');
            $table->string('alias', 64)
                ->comment('唯一标识')
                ->unique();
            $table->string('description')
                ->nullable()
                ->comment('计划描述');
            $table->easyStatus();
            $table->sort();
            $table->timestamps();
            $table->softDeletes()
                ->comment('软删除时间');
        });

        Schema::create('tasks', static function (Blueprint $table) {
            $table->comment('结算任务');
            $table->id();
            $table->tenant();
            $table->foreignId('plan_id')
                ->index()
                ->constrained()
                ->cascadeOnDelete();
            $table->string('name')
                ->comment('步骤名称');
            $table->easyStatus();
            $table->string('service')
                ->comment('此步骤挂载的服务');
            $table->jsonb('options')
                ->nullable()
                ->comment('参数');
            $table->sort();
            $table->timestamps();
        });

        Schema::create('vouchers', static function (Blueprint $table) {
            $table->comment('凭据表');
            $table->id();
            $table->tenant();
            $table->no();
            $table->foreignId('plan_id')
                ->index()
                ->constrained()
                ->cascadeOnDelete();
            $table->user();
            $table->morphs('target');
            $table->string('status', 32)
                ->index()
                ->comment('结算状态:枚举值');
            $table->longText('exception')
                ->nullable()
                ->comment('异常信息');
            $table->timestamp('completed_at')
                ->nullable()
                ->comment('完成时间');
            $table->timestamp('scheduled_at')
                ->nullable()
                ->index()
                ->comment('计划执行时间');
            $table->timestamps();
            $table->softDeletes()
                ->comment('软删除时间');
        });

        Schema::create('voucher_logs', static function (Blueprint $table) {
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
            $table->jsonb('meta')
                ->nullable()
                ->comment('元数据');
            $table->unsignedInteger('duration_ms')
                ->nullable()
                ->comment('执行时间（毫秒）');
            $table->timestamp('created_at')
                ->comment('创建时间');
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_logs');
        Schema::dropIfExists('vouchers');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('plans');
    }
};
