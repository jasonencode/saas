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
        Schema::create('topics', static function (Blueprint $table) {
            $table->comment('专题表');
            $table->id();
            $table->tenant();
            $table->string('name')
                ->comment('专题名称');
            $table->string('slug', 64)
                ->comment('专题标识，用于路由');
            $table->string('description')
                ->nullable()
                ->comment('专题简介');
            $table->cover();
            $table->easyStatus();
            $table->sort();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'slug']);
        });

        Schema::create('topic_product', static function (Blueprint $table) {
            $table->comment('专题商品关联表');
            $table->id();
            $table->unsignedBigInteger('topic_id')
                ->comment('专题ID');
            $table->unsignedBigInteger('product_id')
                ->comment('商品ID');
            $table->sort();
            $table->timestamps();

            $table->unique(['topic_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('topic_product');
        Schema::dropIfExists('topics');
    }
};
