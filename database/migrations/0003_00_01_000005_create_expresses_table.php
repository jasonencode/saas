<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\Mall\DeliveryType;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mall_expresses', static function(Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')
                ->nullable();
            $table->string('phone', 32)
                ->nullable();
            $table->string('cover')
                ->nullable();
            $table->sort();
            $table->easyStatus();
            $table->timestamps();
            $table->softDeletes()
                ->index();
        });

        Schema::create('mall_deliveries', static function(Blueprint $table) {
            $table->id();
            $table->tenant();
            $table->string('name')
                ->comment('模板名称');
            $table->enum('type', DeliveryType::values())
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
        Schema::dropIfExists('mall_deliveries');
        Schema::dropIfExists('mall_expresses');
    }
};
