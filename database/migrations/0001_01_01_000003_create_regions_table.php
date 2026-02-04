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
        Schema::create('regions', static function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')
                ->index()
                ->comment('父级ID');
            $table->string('name')
                ->comment('名称');
            $table->string('pinyin')
                ->nullable()
                ->comment('拼音');
            $table->string('level', 32)
                ->index()
                ->comment('层级');
            $table->integer('order')
                ->default(0)
                ->comment('排序');
            $table->comment('行政区域');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regions');
    }
};
