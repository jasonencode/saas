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
        Schema::create('regions', static function (Blueprint $table) {
            $table->comment('行政区域');
            $table->id();
            $table->unsignedBigInteger('parent_id')
                ->index()
                ->comment('父级ID');
            $table->string('name')
                ->comment('名称');
            $table->string('pinyin')
                ->nullable()
                ->comment('拼音');
            $table->string('level', 32)
                ->index()
                ->comment('层级');
            $table->integer('order')
                ->default(0)
                ->comment('排序');
        });

        Schema::create('addresses', static function (Blueprint $table) {
            $table->comment('用户地址簿');
            $table->id();
            $table->user();
            $table->string('name')
                ->comment('联系人姓名');
            $table->string('mobile')
                ->nullable()
                ->comment('联系人电话');
            $table->unsignedBigInteger('province_id')
                ->index()
                ->comment('省份ID');
            $table->unsignedBigInteger('city_id')
                ->index()
                ->comment('城市ID');
            $table->unsignedBigInteger('district_id')
                ->index()
                ->comment('区县ID');
            $table->string('address')
                ->nullable()
                ->comment('详细地址');
            $table->boolean('is_default')
                ->default(false)
                ->comment('是否为默认地址');
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
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('regions');
    }
};
