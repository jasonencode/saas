<?php

use App\Enums\Gender;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', static function (Blueprint $table) {
            $table->comment('用户主表');
            $table->id();
            $table->tenant();
            $table->string('username')
                ->comment('用户名');
            $table->string('password')
                ->nullable()
                ->comment('密码');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes()
                ->index();

            $table->unique(['tenant_id', 'username']);
        });

        Schema::create('user_profiles', static function (Blueprint $table) {
            $table->comment('用户扩展信息');
            $table->user();
            $table->string('nickname')
                ->nullable()
                ->comment('昵称');
            $table->string('gender', 32)
                ->index()
                ->default(Gender::Secret->value)
                ->comment('性别');
            $table->date('birthday')
                ->nullable()
                ->comment('生日');
            $table->string('avatar')
                ->nullable()
                ->comment('头像');
            $table->string('description')
                ->nullable()
                ->comment('个人简介');
            $table->timestamps();
        });

        Schema::create('login_records', static function (Blueprint $table) {
            $table->comment('登录记录');
            $table->id();
            $table->morphs('user');
            $table->ipAddress('ip')
                ->nullable()
                ->comment('登录IP');
            $table->text('user_agent')
                ->nullable()
                ->comment('UA');
            $table->timestamp('created_at')
                ->comment('登录时间');
        });

        Schema::create('sessions', static function (Blueprint $table) {
            $table->comment('会话表');
            $table->string('id')
                ->primary()
                ->comment('会话ID');
            $table->user();
            $table->ipAddress()
                ->nullable()
                ->comment('IP地址');
            $table->text('user_agent')
                ->nullable()
                ->comment('UA');
            $table->longText('payload')
                ->comment('会话数据');
            $table->integer('last_activity')
                ->index()
                ->comment('最近活动时间戳');
        });

        Schema::create('personal_access_tokens', static function (Blueprint $table) {
            $table->comment('个人访问令牌');
            $table->id();
            $table->morphs('tokenable');
            $table->string('name')
                ->comment('令牌名称');
            $table->string('token', 64)
                ->unique()
                ->comment('令牌摘要');
            $table->text('abilities')
                ->nullable()
                ->comment('权限范围');
            $table->timestamp('last_used_at')
                ->nullable()
                ->comment('上次使用时间');
            $table->timestamp('expires_at')
                ->nullable()
                ->comment('过期时间');
            $table->timestamps();
        });

        Schema::create('user_relations', static function (Blueprint $table) {
            $table->comment('用户推荐关系表');
            $table->unsignedBigInteger('user_id')
                ->primary();
            $table->unsignedBigInteger('parent_id')
                ->index()
                ->default(0)
                ->comment('直接推荐人ID');
            $table->unsignedTinyInteger('layer')
                ->default(1)
                ->index()
                ->comment('所在层级');
            $table->text('path')
                ->nullable()
                ->comment('关系路径 格式: /1/2/3/');
            $table->unsignedInteger('direct_count')
                ->default(0)
                ->comment('直推人数');
            $table->unsignedInteger('team_count')
                ->default(0)
                ->comment('团队人数');
            $table->timestamps();

            if (config('database.default') === 'pgsql') {
                DB::statement('CREATE INDEX idx_user_relations_path_prefix ON user_relations (path text_pattern_ops)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_relations');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('login_records');
        Schema::dropIfExists('user_infos');
        Schema::dropIfExists('users');
    }
};
