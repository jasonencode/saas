<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attachments', static function(Blueprint $table) {
            $table->uuid('id')
                ->unique();
            $table->string('name')
                ->nullable()
                ->comment('原始名称');
            $table->string('hash', 40)
                ->index()
                ->comment('文件哈希');
            $table->string('extension', 16)
                ->comment('原始扩展名');
            $table->string('mime', 64)
                ->comment('文件类型');
            $table->unsignedBigInteger('size')
                ->comment('文件大小：字节');
            $table->string('disk', 32)
                ->nullable()
                ->comment('存储磁盘');
            $table->string('path')
                ->comment('存储路径');
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
