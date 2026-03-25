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
        Schema::create('notifications', static function (Blueprint $table) {
            $table->comment('系统通知表');
            $table->uuid('id')
                ->primary()
                ->comment('通知ID');
            $table->string('type')
                ->index()
                ->comment('通知类型');
            $table->morphs('notifiable');
            $table->jsonb('data')
                ->comment('通知数据载荷');
            $table->timestamp('read_at')
                ->index()
                ->nullable()
                ->comment('读取时间');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
