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
        Schema::create('aliyuns', static function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('app_id');
            $table->string('app_secret');
            $table->easyStatus();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('wechats', static function (Blueprint $table) {
            $table->id();
            $table->tenant();
            $table->string('name');
            $table->string('app_id');
            $table->string('app_secret');
            $table->easyStatus();
            $table->boolean('is_connected')
                ->default(false)
                ->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('wechat_payments', static function (Blueprint $table) {
            $table->id();
            $table->tenant();
            $table->unsignedBigInteger('wechat_id')
                ->index();
            $table->string('name');
            $table->string('mch_id');
            $table->string('secret');
            $table->text('public_key')
                ->nullable();
            $table->text('private_key')
                ->nullable();
            $table->easyStatus();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wechat_payments');
        Schema::dropIfExists('wechats');
        Schema::dropIfExists('aliyuns');
    }
};
