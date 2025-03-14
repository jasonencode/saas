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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
