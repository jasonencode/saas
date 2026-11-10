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
        // 加密后的密文长度约为明文的 1.3~1.5 倍，varchar(255) 会溢出，统一扩为 text
        Schema::table('tenants', static function (Blueprint $table) {
            $table->text('app_secret')
                ->nullable()
                ->comment('APP SECRET')
                ->change();
        });

        Schema::table('socialite_accounts', static function (Blueprint $table) {
            $table->text('app_secret')
                ->comment('密钥')
                ->change();
        });

        Schema::table('aliyuns', static function (Blueprint $table) {
            $table->text('app_secret')
                ->change();
        });

        Schema::table('wechats', static function (Blueprint $table) {
            $table->text('app_secret')
                ->change();
        });

        Schema::table('wechat_minis', static function (Blueprint $table) {
            $table->text('app_secret')
                ->change();
        });

        Schema::table('wechat_payments', static function (Blueprint $table) {
            $table->text('secret')
                ->change();
        });

        Schema::table('socialites', static function (Blueprint $table) {
            $table->text('access_token')
                ->nullable()
                ->comment('三方平台的访问令牌')
                ->change();
            $table->text('refresh_token')
                ->nullable()
                ->comment('三方平台的刷新令牌')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * 密文无法缩回 varchar(255)，回滚前需先执行 `php artisan system:decrypt-secrets`
     */
    public function down(): void
    {
        //
    }
};
