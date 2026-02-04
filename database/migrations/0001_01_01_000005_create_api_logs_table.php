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
        Schema::create('api_logs', static function (Blueprint $table) {
            $table->id();
            $table->string('user_type')
                ->nullable()
                ->comment('用户类型');
            $table->unsignedBigInteger('user_id')
                ->nullable()
                ->comment('用户ID');
            $table->string('method', 32)
                ->index()
                ->comment('HTTP方法');
            $table->string('path')
                ->comment('请求路径');
            $table->string('ip', 64)
                ->index()
                ->comment('IP地址');
            $table->text('user_agent')
                ->nullable()
                ->comment('UA');
            $table->unsignedSmallInteger('status_code')
                ->default(0)
                ->comment('状态码');
            $table->unsignedInteger('duration')
                ->default(0)
                ->comment('耗时毫秒');
            $table->longText('input')
                ->nullable()
                ->comment('请求入参');
            $table->longText('output')
                ->nullable()
                ->comment('响应结果');
            $table->timestamp('created_at')
                ->comment('记录时间');

            $table->index(['user_type', 'user_id']);
            $table->comment('API访问日志');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_logs');
    }
};
