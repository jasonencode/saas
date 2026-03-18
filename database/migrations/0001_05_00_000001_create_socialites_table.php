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
        Schema::create('socialite_accounts', static function (Blueprint $table) {
            $table->id();
            $table->tenant();
            $table->string('provider', 64)
                ->index()
                ->comment('三方平台类型，枚举类型');
            $table->string('name')
                ->comment('账户名称');
            $table->string('app_key')
                ->comment('账户KEY');
            $table->string('app_secret')
                ->comment('密钥');
            $table->timestamps();

            $table->softDeletes();
        });

        Schema::create('socialites', static function (Blueprint $table) {
            $table->id();
            $table->tenant();
            $table->unsignedBigInteger('account_id')
                ->index();
            $table->unsignedBigInteger('user_id')
                ->index()
                ->nullable()
                ->comment('关联的用户ID');
            $table->string('provider_id')
                ->index()
                ->comment('三方平台的用户唯一标识');
            $table->string('union_id')
                ->index()
                ->nullable()
                ->comment('微信专有的UNION_ID');
            $table->string('access_token')
                ->comment('三方平台的访问令牌')
                ->nullable();
            $table->string('refresh_token')
                ->comment('三方平台的刷新令牌')
                ->nullable();
            $table->dateTime('expired_at')
                ->comment('令牌过期时间')
                ->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('socialites');
        Schema::dropIfExists('socialite_accounts');
    }
};