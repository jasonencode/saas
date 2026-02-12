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
        Schema::create('chain_addresses', static function (Blueprint $table) {
            $table->id();
            $table->tenant();
            $table->unsignedBigInteger('network_id')
                ->index();
            $table->string('name')
                ->nullable();
            $table->string('address', 64)
                ->comment('地址');
            $table->string('private_key')
                ->comment('私钥');
            $table->string('remark')
                ->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chain_addresses');
    }
};
