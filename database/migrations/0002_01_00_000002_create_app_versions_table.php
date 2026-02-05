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
        Schema::create('app_versions', static function (Blueprint $table) {
            $table->comment('APP版本发布记录');
            $table->id();
            $table->string('platform', 64)
                ->index()
                ->comment('平台');
            $table->string('application_id')
                ->index()
                ->comment('包名');
            $table->string('version', 16)
                ->index()
                ->comment('版本号');
            $table->boolean('force')
                ->default(0)
                ->comment('强制更新');
            $table->json('description')
                ->nullable()
                ->comment('升级说明');
            $table->string('download_url')
                ->nullable()
                ->comment('下载地址');
            $table->timestamp('publish_at')
                ->nullable()
                ->comment('发布时间');
            $table->timestamps();
            $table->softDeletes()
                ->index();

            $table->unique(['platform', 'application_id', 'version']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_versions');
    }
};
