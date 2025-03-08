<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('addresses', function(Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')
                ->index();
            $table->string('name');
            $table->string('mobile')
                ->nullable();
            $table->unsignedBigInteger('province_id')
                ->index();
            $table->unsignedBigInteger('city_id')
                ->index();
            $table->unsignedBigInteger('district_id')
                ->index();
            $table->string('address')
                ->nullable()
                ->comment('详细地址');
            $table->boolean('is_default')
                ->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
