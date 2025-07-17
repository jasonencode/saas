<?php

use App\Enums\AdminType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('administrators', static function(Blueprint $table) {
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

        Schema::create('tenants', static function(Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')
                ->unique();
            $table->dateTime('expired_at')
                ->nullable();
            $table->string('avatar')
                ->nullable();
            $table->easyStatus();
            $table->json('config')
                ->nullable();
            $table->string('app_key')
                ->index();
            $table->string('app_secret');
            $table->timestamps();

            $table->softDeletes();
        });

        Schema::create('admin_roles', static function(Blueprint $table) {
            $table->id();
            $table->tenant();
            $table->string('name');
            $table->string('description')
                ->nullable();
            $table->boolean('is_sys')
                ->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('administrator_role', static function(Blueprint $table) {
            $table->id();
            $table->foreignId('administrator_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('role_id')
                ->constrained('admin_roles')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['administrator_id', 'role_id']);
        });

        Schema::create('admin_role_permissions', static function(Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')
                ->constrained('admin_roles')
                ->cascadeOnDelete();
            $table->string('policy')
                ->nullable();
            $table->string('method')
                ->nullable();
            $table->timestamp('created_at');

            $table->unique(['role_id', 'policy', 'method']);
        });

        Schema::create('systems', static function(Blueprint $table) {
            $table->id();
            $table->string('username');
            $table->timestamps();
        });

        Schema::create('administrator_tenant', static function(Blueprint $table) {
            $table->foreignId('administrator_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->tenant();
            $table->timestamps();

            $table->unique(['administrator_id', 'tenant_id']);
        });
    }

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
