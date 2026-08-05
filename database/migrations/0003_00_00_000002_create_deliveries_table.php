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
        Schema::create('deliveries', static function (Blueprint $table) {
            $table->comment('运费模板表');
            $table->id();
            $table->tenant();
            $table->string('name')
                ->comment('模板名称');
            $table->string('type', 16)
                ->index()
                ->comment('计费方式');
            $table->decimal('first', 12)
                ->unsigned()
                ->default(0)
                ->comment('首件(个)/首重(Kg)');
            $table->decimal('first_fee', 12)
                ->unsigned()
                ->default(0)
                ->comment('运费(元)');
            $table->decimal('additional', 12)
                ->unsigned()
                ->default(0)
                ->comment('续件/续重');
            $table->decimal('additional_fee', 12)
                ->unsigned()
                ->default(0)
                ->comment('续费(元)');
            $table->decimal('free_shipping_threshold', 12)
                ->unsigned()
                ->default(0)
                ->comment('包邮门槛(元)');
            $table->boolean('is_default')
                ->default(false)
                ->index()
                ->comment('是否默认模板');
            $table->easyStatus();
            $table->timestamps();
            $table->softDeletes()
                ->index();
        });

        Schema::create('delivery_rules', static function (Blueprint $table) {
            $table->comment('运费规则表（按地区差异化运费）');
            $table->id();
            $table->unsignedBigInteger('delivery_id')
                ->index();
            $table->unsignedBigInteger('province_id')
                ->nullable()
                ->index()
                ->comment('省份ID');
            $table->unsignedBigInteger('city_id')
                ->nullable()
                ->index()
                ->comment('城市ID');
            $table->unsignedBigInteger('district_id')
                ->nullable()
                ->index()
                ->comment('区县ID');
            $table->string('region_code', 32)
                ->nullable()
                ->comment('地区编码（省/市/区）');
            $table->string('region_name', 128)
                ->nullable()
                ->comment('地区名称');
            $table->decimal('first', 12)
                ->unsigned()
                ->default(0)
                ->comment('首件(个)/首重(Kg)');
            $table->decimal('first_fee', 12)
                ->unsigned()
                ->default(0)
                ->comment('运费(元)');
            $table->decimal('additional', 12)
                ->unsigned()
                ->default(0)
                ->comment('续件/续重');
            $table->decimal('additional_fee', 12)
                ->unsigned()
                ->default(0)
                ->comment('续费(元)');
            $table->decimal('free_shipping_threshold', 12)
                ->unsigned()
                ->default(0)
                ->comment('包邮门槛(元)');
            $table->sort();
            $table->timestamps();

            $table->foreign('delivery_id')
                ->references('id')
                ->on('deliveries')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_rules');
        Schema::dropIfExists('deliveries');
    }
};
