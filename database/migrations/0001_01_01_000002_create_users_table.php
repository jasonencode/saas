<?php

use App\Enums\Gender;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function(Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')
                ->index();
            $table->string('username');
            $table->string('password')
                ->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'username']);
        });

        Schema::create('user_infos', function(Blueprint $table) {
            $table->unsignedBigInteger('user_id')
                ->primary();
            $table->string('nickname')
                ->nullable();
            $table->enum('gender', Gender::values())
                ->default(Gender::Secret->value);
            $table->date('birthday')
                ->nullable();
            $table->string('avatar')
                ->nullable();
            $table->string('description')
                ->nullable();
            $table->timestamps();
        });

        Schema::create('login_records', function(Blueprint $table) {
            $table->id();
            $table->morphs('user');
            $table->string('ip', 16)
                ->nullable();
            $table->text('user_agent')
                ->nullable();
            $table->timestamp('created_at');
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('user_infos');
        Schema::dropIfExists('login_records');
        Schema::dropIfExists('sessions');
    }
};
