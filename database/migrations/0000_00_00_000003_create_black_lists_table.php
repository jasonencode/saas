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
        Schema::create('black_lists', static function (Blueprint $table) {
            $table->comment('IP黑名单表');
            $table->id();
            $table->ipAddress('ip')
                ->comment('IP地址');
            $table->string('remark')
                ->nullable()
                ->comment('备注');
            $table->timestamp('created_at')
                ->comment('创建时间');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('black_lists');
    }
};
