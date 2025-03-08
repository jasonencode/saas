<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
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

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id')
                ->index();
            $table->string('name');
            $table->string('description')
                ->nullable();
            $table->boolean('is_sys')
                ->default(false);
            $table->timestamps();

            $table->softDeletes();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('role_permissions');
    }
};
