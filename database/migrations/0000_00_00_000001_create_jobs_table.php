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
        Schema::create('jobs', static function (Blueprint $table) {
            $table->id();
            $table->string('queue')
                ->index()
                ->comment('队列名称');
            $table->longText('payload')
                ->comment('任务载荷');
            $table->unsignedTinyInteger('attempts')
                ->comment('尝试次数');
            $table->unsignedInteger('reserved_at')
                ->nullable()
                ->comment('保留时间戳');
            $table->unsignedInteger('available_at')
                ->comment('可执行时间戳');
            $table->unsignedInteger('created_at')
                ->comment('创建时间戳');
            $table->comment('队列任务表');
        });

        Schema::create('job_batches', static function (Blueprint $table) {
            $table->string('id')
                ->primary()
                ->comment('批次ID');
            $table->string('name')
                ->comment('批次名称');
            $table->integer('total_jobs')
                ->comment('任务总数');
            $table->integer('pending_jobs')
                ->comment('待处理任务数');
            $table->integer('failed_jobs')
                ->comment('失败任务数');
            $table->longText('failed_job_ids')
                ->comment('失败任务ID列表');
            $table->mediumText('options')
                ->nullable()
                ->comment('批次选项');
            $table->integer('cancelled_at')
                ->nullable()
                ->comment('取消时间戳');
            $table->integer('created_at')
                ->comment('创建时间戳');
            $table->integer('finished_at')
                ->nullable()
                ->comment('完成时间戳');
            $table->comment('批量任务表');
        });

        Schema::create('failed_jobs', static function (Blueprint $table) {
            $table->id();
            $table->string('uuid')
                ->unique()
                ->comment('任务唯一标识');
            $table->text('connection')
                ->comment('连接名称');
            $table->text('queue')
                ->comment('队列名称');
            $table->longText('payload')
                ->comment('任务载荷');
            $table->longText('exception')
                ->comment('异常详情');
            $table->timestamp('failed_at')
                ->useCurrent()
                ->comment('失败时间');
            $table->comment('失败任务表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
    }
};
