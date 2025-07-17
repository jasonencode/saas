<?php

use App\Enums\RegionLevel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('regions', static function(Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')
                ->constrained('regions')
                ->cascadeOnDelete();
            $table->string('name');
            $table->string('pinyin')
                ->nullable();
            $table->enum('level', RegionLevel::values());
            $table->integer('order')
                ->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regions');
    }
};
