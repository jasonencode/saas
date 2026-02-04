<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('expresses', static function (Blueprint $table) {
            $table->id();
            $table->string('name')
                ->comment('快递公司名称');
            $table->string('code')
                ->nullable()
                ->comment('快递公司代码');
            $table->string('phone', 32)
                ->nullable()
                ->comment('联系电话');
            $table->cover();
            $table->sort();
            $table->easyStatus();
            $table->timestamps();
            $table->softDeletes()
                ->index();
        });

        Schema::create('deliveries', static function (Blueprint $table) {
            $table->id();
            $table->tenant();
            $table->string('name')
                ->comment('模板名称');
            $table->string('type', 16)
                ->index()
                ->comment('计费方式');
            $table->decimal('first')
                ->default(0)
                ->comment('首件(个)/首重(Kg)');
            $table->decimal('first_fee')
                ->default(0)
                ->comment('运费(元)');
            $table->decimal('additional')
                ->default(0)
                ->comment('续件/续重');
            $table->decimal('additional_fee')
                ->default(0)
                ->comment('续费(元)');
            $table->timestamps();

            $table->softDeletes()
                ->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');
        Schema::dropIfExists('expresses');
    }
};
