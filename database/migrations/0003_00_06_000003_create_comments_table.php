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
        Schema::create('comments', static function (Blueprint $table) {
            $table->comment('评论');
            $table->id();
            $table->user();
            $table->morphs('commentable');
            $table->unsignedTinyInteger('star')
                ->default(0)
                ->comment('评分');
            $table->pictures();
            $table->string('content')
                ->nullable()
                ->comment('评论内容');
            $table->easyStatus();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
