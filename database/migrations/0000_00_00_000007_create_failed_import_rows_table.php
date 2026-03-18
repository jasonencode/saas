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
        Schema::create('failed_import_rows', static function (Blueprint $table) {
            $table->comment('导入失败的行记录');
            $table->id();
            $table->jsonb('data')
                ->comment('原始数据');
            $table->foreignId('import_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->text('validation_error')
                ->nullable()
                ->comment('校验错误信息');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('failed_import_rows');
    }
};
