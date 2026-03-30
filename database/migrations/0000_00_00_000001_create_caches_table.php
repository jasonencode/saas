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
        Schema::create('cache', static function (Blueprint $table) {
            $table->comment('应用缓存键值对');
            $table->string('key')
                ->primary()
                ->comment('缓存键');
            $table->mediumText('value')
                ->comment('缓存值');
            $table->bigInteger('expiration')
                ->comment('过期时间戳');
        });

        Schema::create('cache_locks', static function (Blueprint $table) {
            $table->comment('缓存锁表');
            $table->string('key')
                ->primary()
                ->comment('锁键');
            $table->string('owner')
                ->comment('锁拥有者标识');
            $table->bigInteger('expiration')
                ->comment('锁过期时间戳');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};
