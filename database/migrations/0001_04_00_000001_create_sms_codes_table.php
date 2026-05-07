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
        Schema::create('sms_codes', static function (Blueprint $table) {
            $table->comment('短信验证码记录');
            $table->id();
            $table->string('phone', 16)
                ->comment('手机号');
            $table->string('channel', 32)
                ->comment('业务渠道');
            $table->string('gateway', 16)
                ->default('debug')
                ->comment('短信网关');
            $table->string('code', 6)
                ->comment('验证码');
            $table->boolean('used')
                ->default(0)
                ->comment('是否已使用');
            $table->dateTime('expires_at')
                ->comment('过期时间');
            $table->timestamps();

            $table->index(['phone', 'channel', 'used']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_codes');
    }
};
