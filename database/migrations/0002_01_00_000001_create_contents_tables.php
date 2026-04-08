<?php

use App\Enums\Content\CategoryType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contents', static function (Blueprint $table) {
            $table->comment('内容主表');
            $table->id();
            $table->tenant();
            $table->string('title')
                ->comment('标题');
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
            $table->sort();
            $table->timestamps();
            $table->softDeletes()
                ->index();
        });

        Schema::create('categories', static function (Blueprint $table) {
            $table->comment('内容分类');
            $table->id();
            $table->tenant();
            $table->string('type', 16)
                ->default(CategoryType::Content->value)
                ->comment('分类类型');
            $table->unsignedBigInteger('parent_id')
                ->index()
                ->nullable()
                ->comment('上级分类ID');
            $table->unsignedTinyInteger('level')
                ->default(0)
                ->comment('分类层级');
            $table->string('name')
                ->comment('分类名称');
            $table->string('description')
                ->nullable()
                ->comment('简介');
            $table->string('cover')
                ->nullable()
                ->comment('封面图');
            $table->easyStatus();
            $table->sort();
            $table->timestamps();
            $table->softDeletes()
                ->index();
        });

        Schema::create('content_category', static function (Blueprint $table) {
            $table->comment('内容与分类关联');
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_category');
        Schema::dropIfExists('contents');
        Schema::dropIfExists('categories');
    }
};
