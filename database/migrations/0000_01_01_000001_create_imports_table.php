<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('imports', static function(Blueprint $table) {
            $table->id();
            $table->timestamp('completed_at')
                ->nullable();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('importer');
            $table->unsignedInteger('processed_rows')
                ->default(0);
            $table->unsignedInteger('total_rows');
            $table->unsignedInteger('successful_rows')
                ->default(0);
            $table->morphs('user');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imports');
    }
};
