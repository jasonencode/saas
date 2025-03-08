<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('staffers', function(Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->boolean('status')
                ->default(true);
            $table->string('avatar')
                ->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('staffer_team', function(Blueprint $table) {
            $table->unsignedBigInteger('staffer_id')
                ->index();
            $table->foreignId('team_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['staffer_id', 'team_id']);
        });

        Schema::create('staffer_role', function(Blueprint $table) {
            $table->unsignedBigInteger('staffer_id')
                ->index();
            $table->unsignedBigInteger('role_id')
                ->index();
            $table->timestamps();

            $table->unique(['staffer_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staffers');
        Schema::dropIfExists('staffer_team');
        Schema::dropIfExists('staffer_role');
    }
};
