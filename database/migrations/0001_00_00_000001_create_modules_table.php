<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('name')
                ->unique();
            $table->string('version', 16)
                ->nullable();
            $table->string('module_id')
                ->nullable();
            $table->string('alias')
                ->nullable();
            $table->string('description')
                ->nullable();
            $table->string('priority')
                ->nullable();
            $table->string('author')
                ->nullable();
            $table->boolean('status')
                ->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
