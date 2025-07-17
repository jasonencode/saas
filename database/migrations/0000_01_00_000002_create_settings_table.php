<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('settings', static function(Blueprint $table) {
            $table->id();
            $table->string('module', 32)
                ->index();
            $table->string('key')
                ->index();
            $table->text('value')
                ->nullable();
            $table->timestamps();

            $table->unique(['module', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
