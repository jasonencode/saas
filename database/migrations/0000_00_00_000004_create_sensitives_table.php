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
        Schema::create('sensitives', static function (Blueprint $table) {
            $table->comment('敏感词库');
            $table->id();
            $table->string('keywords')
                ->comment('敏感关键词');
            $table->timestamp('created_at')
                ->comment('创建时间');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensitives');
    }
};
