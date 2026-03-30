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
        Schema::create('administrators', static function (Blueprint $table) {
            $table->comment('后台管理员');
            $table->id();
            $table->string('type', 32)
                ->comment('账号类型');
            $table->string('username')
                ->unique()
                ->comment('登录账号');
            $table->string('password')
                ->comment('密码');
            $table->rememberToken();
            $table->string('name')
                ->nullable()
                ->comment('显示名称');
            $table->string('avatar')
                ->nullable()
                ->comment('头像');
            $table->timestamps();
            $table->softDeletes()
                ->index();
        });

        Schema::create('tenants', static function (Blueprint $table) {
            $table->comment('租户信息');
            $table->id();
            $table->string('name')
                ->comment('租户名称');
            $table->string('slug')
                ->unique()
                ->comment('租户唯一标识');
            $table->dateTime('expired_at')
                ->nullable()
                ->comment('过期时间');
            $table->string('avatar')
                ->nullable()
                ->comment('租户头像');
            $table->string('app_key')
                ->nullable()
                ->index()
                ->comment('APP KEY');
            $table->string('app_secret')
                ->nullable()
                ->comment('APP SECRET');
            $table->easyStatus();
            $table->jsonb('config')
                ->nullable()
                ->comment('租户配置');
            $table->timestamps();

            $table->softDeletes()
                ->index();
        });

        Schema::create('admin_roles', static function (Blueprint $table) {
            $table->comment('后台角色');
            $table->id();
            $table->tenant();
            $table->string('name')
                ->comment('角色名称');
            $table->string('description')
                ->nullable()
                ->comment('角色描述');
            $table->boolean('is_sys')
                ->default(false)
                ->comment('系统内置标记');
            $table->timestamps();
            $table->softDeletes()
                ->index();
        });

        Schema::create('administrator_role', static function (Blueprint $table) {
            $table->comment('管理员与角色关联');
            $table->foreignId('administrator_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('role_id')
                ->constrained('admin_roles')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['administrator_id', 'role_id']);
        });

        Schema::create('admin_role_permissions', static function (Blueprint $table) {
            $table->comment('角色权限配置');
            $table->id();
            $table->foreignId('role_id')
                ->constrained('admin_roles')
                ->cascadeOnDelete();
            $table->string('policy')
                ->nullable()
                ->comment('策略类');
            $table->string('method')
                ->nullable()
                ->comment('方法名');
            $table->timestamp('created_at')
                ->comment('创建时间');

            $table->unique(['role_id', 'policy', 'method']);
        });

        Schema::create('systems', static function (Blueprint $table) {
            $table->comment('系统账号表');
            $table->id();
            $table->string('name')
                ->comment('系统账号');
            $table->timestamps();
        });

        Schema::create('administrator_tenant', static function (Blueprint $table) {
            $table->comment('管理员与租户关联');
            $table->foreignId('administrator_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->tenant();
            $table->timestamps();

            $table->unique(['administrator_id', 'tenant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_role_permissions');
        Schema::dropIfExists('systems');
        Schema::dropIfExists('administrator_tenant');
        Schema::dropIfExists('administrator_role');
        Schema::dropIfExists('admin_roles');
        Schema::dropIfExists('administrators');
        Schema::dropIfExists('tenants');
    }
};
