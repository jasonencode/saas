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
        Schema::create('mall_banners', static function(Blueprint $table) {
            $table->id();
            $table->tenant();
            $table->cover();
            $table->string('title')
                ->comment('横幅标题');
            $table->string('jump')
                ->nullable()
                ->comment('跳转链接');
            $table->sort();
            $table->easyStatus();
            $table->timestamps();
            $table->softDeletes()
                ->index();

            $table->index(['created_at']);
            $table->index(['status', 'sort']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mall_banners');
    }
};
