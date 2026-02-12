<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('networks', static function (Blueprint $table) {
            $table->id();
            $table->tenant();
            $table->string('name')
                ->comment('区块链网络名称');
            $table->string('type', 32)
                ->index()
                ->comment('区块链网络类型，枚举类型');
            $table->string('rpc_url')
                ->nullable()
                ->comment('RPC请求地址');
            $table->string('explorer_url')
                ->nullable()
                ->comment('浏览器地址');
            $table->boolean('status')
                ->default(false)
                ->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('networks');
    }
};
