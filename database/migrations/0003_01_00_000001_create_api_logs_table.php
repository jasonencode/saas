<?php

use App\Enums\HttpMethod;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('api_logs', function(Blueprint $table) {
            $table->id();
            $table->string('user_type')
                ->nullable();
            $table->unsignedBigInteger('user_id')
                ->nullable();
            $table->enum('method', HttpMethod::values())
                ->index();
            $table->string('path');
            $table->unsignedBigInteger('ip')
                ->index();
            $table->string('user_agent')
                ->nullable();
            $table->unsignedMediumInteger('status_code')
                ->default(0);
            $table->unsignedInteger('duration')
                ->default(0);
            $table->longText('input')
                ->nullable();
            $table->longText('output')
                ->nullable();
            $table->timestamp('created_at');
            $table->index(['user_type', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_logs');
    }
};
