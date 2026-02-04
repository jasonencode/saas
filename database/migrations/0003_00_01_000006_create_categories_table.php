<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mall_categories', static function(Blueprint $table) {
            $table->id();
            $table->tenant();
            $table->unsignedBigInteger('parent_id')
                ->index()
                ->nullable();
            $table->string('name');
            $table->string('description')
                ->nullable();
            $table->cover();
            $table->easyStatus();
            $table->sort();
            $table->jsonb('ext')
                ->nullable()
                ->comment('扩展信息');
            $table->timestamps();
            $table->softDeletes()
                ->index();

            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mall_categories');
    }
};
