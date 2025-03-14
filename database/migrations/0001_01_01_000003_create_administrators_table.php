<?php

use App\Enums\AdminType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('administrators', function(Blueprint $table) {
            $table->id();
            $table->enum('type', AdminType::values());
            $table->string('username')
                ->unique();
            $table->string('password');
            $table->rememberToken();
            $table->string('name')
                ->nullable();
            $table->string('avatar')
                ->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('administrator_role', function(Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('administrator_id')
                ->index();
            $table->unsignedBigInteger('role_id')
                ->index();
            $table->timestamps();

            $table->unique(['administrator_id', 'role_id']);
        });

        Schema::create('admin_roles', function(Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')
                ->index()
                ->nullable();
            $table->string('name');
            $table->string('description')
                ->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('admin_role_permissions', function(Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id')
                ->index();
            $table->string('policy')
                ->nullable();
            $table->string('method')
                ->nullable();
            $table->timestamp('created_at');

            $table->unique(['role_id', 'policy', 'method']);
        });

        Schema::create('systems', function(Blueprint $table) {
            $table->id();
            $table->string('username');
            $table->timestamps();
        });

        Schema::create('tenants', function(Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')
                ->unique();
            $table->dateTime('expired_at')
                ->nullable();
            $table->string('avatar')
                ->nullable();
            $table->boolean('status')
                ->default(true);
            $table->json('configs')
                ->nullable();
            $table->timestamps();

            $table->softDeletes();
        });

        Schema::create('administrator_tenant', function(Blueprint $table) {
            $table->unsignedBigInteger('administrator_id')
                ->index();
            $table->unsignedBigInteger('tenant_id')
                ->index();
            $table->timestamps();

            $table->unique(['administrator_id', 'tenant_id']);
        });

        Schema::create('operation_logs', function(Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('administrator_id')
                ->nullable()
                ->index();
            $table->integer('status')
                ->nullable();
            $table->string('method', 16)
                ->nullable();
            $table->string('url')
                ->nullable();
            $table->json('query')
                ->nullable();
            $table->text('user_agent')
                ->nullable();
            $table->json('log')
                ->nullable();

            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('administrators');
        Schema::dropIfExists('administrator_role');
        Schema::dropIfExists('admin_roles');
        Schema::dropIfExists('admin_role_permissions');
        Schema::dropIfExists('systems');
        Schema::dropIfExists('operation_logs');
        Schema::dropIfExists('tenants');
        Schema::dropIfExists('administrator_tenant');
    }
};
