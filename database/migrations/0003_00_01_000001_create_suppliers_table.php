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
        Schema::create('suppliers', static function (Blueprint $table) {
            $table->id();
            $table->tenant();
            $table->cover();
            $table->string('name')
                ->comment('供应商名称');
            $table->string('remark')
                ->nullable()
                ->comment('供应商备注');
            $table->sort();
            $table->easyStatus();
            $table->timestamps();
            $table->softDeletes()
                ->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
