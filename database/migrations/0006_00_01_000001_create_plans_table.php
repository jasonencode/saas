<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plans', static function (Blueprint $table) {
            $table->comment('结算计划');
            $table->id();
            $table->string('name')
                ->comment('计划名称');
            $table->string('alias', 64)
                ->comment('唯一标识')
                ->unique();
            $table->string('description')
                ->nullable();
            $table->easyStatus();
            $table->sort();
            $table->timestamps();

            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
