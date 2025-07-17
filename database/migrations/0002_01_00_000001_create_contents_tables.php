<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contents', static function(Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('sub_title')
                ->nullable()
                ->comment('副标题');
            $table->string('description')
                ->nullable()
                ->comment('简介');
            $table->string('cover')
                ->nullable()
                ->comment('封面图');
            $table->string('author')
                ->nullable()
                ->comment('作者');
            $table->string('source')
                ->nullable()
                ->comment('内容来源');
            $table->longText('content')
                ->nullable()
                ->comment('内容正文');
            $table->easyStatus();
            $table->unsignedInteger('views')
                ->default(0)
                ->comment('浏览次数');
            $table->integer('sort')
                ->default(0)
                ->comment('排序，数字越大越靠前');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('categories', static function(Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')
                ->index()
                ->nullable();
            $table->unsignedTinyInteger('level')
                ->default(0)
                ->comment('分类层级');
            $table->string('name');
            $table->string('description')
                ->nullable()
                ->comment('简介');
            $table->string('cover')
                ->nullable();
            $table->easyStatus();
            $table->integer('sort')
                ->default(0)
                ->comment('排序，数字越大越靠前');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('content_category', static function(Blueprint $table) {
            $table->foreignId('content_id')
                ->constrained('contents')
                ->cascadeOnDelete();
            $table->foreignId('category_id')
                ->constrained('categories')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['content_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_category');
        Schema::dropIfExists('contents');
        Schema::dropIfExists('categories');
    }
};
