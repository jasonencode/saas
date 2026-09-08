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
        Schema::create('suggests', static function (Blueprint $table) {
            $table->comment('意见反馈');
            $table->id();
            $table->user();
            $table->string('type', 20)
                ->comment('反馈类型');
            $table->string('contact', 100)
                ->nullable()
                ->comment('联系方式');
            $table->string('status', 20)
                ->default('pending')
                ->comment('状态');
            $table->timestamps();

            $table->index(['user_id', 'status', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index('type');
        });

        Schema::create('suggest_messages', static function (Blueprint $table) {
            $table->comment('反馈消息');
            $table->id();
            $table->foreignId('suggest_id')
                ->constrained()
                ->cascadeOnDelete()
                ->comment('反馈ID');
            $table->nullableMorphs('sender');
            $table->text('content')
                ->comment('消息内容');
            $table->timestamps();

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suggest_messages');
        Schema::dropIfExists('suggests');
    }
};
