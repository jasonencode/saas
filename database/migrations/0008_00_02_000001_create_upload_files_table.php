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
        Schema::create('upload_files', static function (Blueprint $table) {
            $table->comment('上传文件记录表');
            $table->uuid('id')
                ->primary()
                ->comment('主键 UUID');
            $table->user();
            $table->string('original_name')
                ->comment('原始文件名');
            $table->string('path')
                ->comment('存储路径');
            $table->string('mime_type', 128)
                ->nullable()
                ->comment('文件类型');
            $table->unsignedBigInteger('size')
                ->default(0)
                ->comment('文件大小（字节）');
            $table->string('disk', 32)
                ->comment('存储磁盘');
            $table->timestamp('uploaded_at')
                ->index()
                ->comment('上传时间');

            $table->index('mime_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('upload_files');
    }
};
