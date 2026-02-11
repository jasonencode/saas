<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tasks', static function(Blueprint $table) {
            $table->comment('结算任务');
            $table->id();
            $table->unsignedBigInteger('plan_id')
                ->index();
            $table->string('name');
            $table->boolean('status')
                ->default(false);
            $table->string('service')
                ->comment('此步骤挂载的服务');
            $table->json('options')
                ->nullable()
                ->comment('参数');
            $table->integer('sort')
                ->default(0)
                ->comment('执行顺序');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
