<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mall_brands', static function(Blueprint $table) {
            $table->id();
            $table->tenant();
            $table->string('name')
                ->comment('品牌名称');
            $table->string('description')
                ->nullable()
                ->comment('品牌描述');
            $table->cover();
            $table->sort();
            $table->jsonb('ext')
                ->nullable()
                ->comment('扩展信息');
            $table->easyStatus();
            $table->timestamps();
            $table->softDeletes()
                ->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mall_brands');
    }
};
