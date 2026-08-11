<?php

use App\Enums\Content\TagType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tags', static function (Blueprint $table) {
            $table->comment('标签表');
            $table->id();
            $table->tenant();
            $table->string('type', 16)
                ->index()
                ->default(TagType::Content->value)
                ->comment('标签类型');
            $table->string('name')
                ->comment('标签名称');
            $table->sort();
            $table->timestamps();
            $table->softDeletes()
                ->index();
            $table->unique(['tenant_id', 'type', 'name']);
        });

        Schema::create('content_tag', static function (Blueprint $table) {
            $table->comment('内容标签关联表');
            $table->id();
            $table->unsignedBigInteger('content_id')
                ->index();
            $table->unsignedBigInteger('tag_id')
                ->index();
            $table->timestamps();

            $table->unique(['content_id', 'tag_id']);
        });

        Schema::create('product_tag', static function (Blueprint $table) {
            $table->comment('商品标签关联表');
            $table->id();
            $table->unsignedBigInteger('product_id')
                ->index();
            $table->unsignedBigInteger('tag_id')
                ->index();
            $table->timestamps();

            $table->unique(['product_id', 'tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_tag');
        Schema::dropIfExists('content_tag');
        Schema::dropIfExists('tags');
    }
};
