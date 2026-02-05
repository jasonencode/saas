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
        Schema::create('exports', static function (Blueprint $table) {
            $table->comment('数据导出任务记录');
            $table->id();
            $table->timestamp('completed_at')
                ->nullable()
                ->comment('完成时间');
            $table->string('file_disk')
                ->comment('文件存储磁盘');
            $table->string('file_name')
                ->nullable()
                ->comment('导出文件名');
            $table->string('exporter')
                ->comment('导出器类/驱动');
            $table->unsignedInteger('processed_rows')
                ->default(0)
                ->comment('已处理行数');
            $table->unsignedInteger('total_rows')
                ->comment('总行数');
            $table->unsignedInteger('successful_rows')
                ->default(0)
                ->comment('成功行数');
            $table->morphs('user');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exports');
    }
};
