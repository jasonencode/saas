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

        Schema::create('contracts', static function (Blueprint $table) {
            $table->comment('智能合约存储');
            $table->id();
            $table->tenant();
            $table->unsignedBigInteger('deployer_id')
                ->index();
            $table->string('name');
            $table->string('address')
                ->nullable()
                ->comment('合约地址');
            $table->string('hash')
                ->nullable()
                ->comment('部署交易的hash');
            $table->string('parameter')
                ->nullable()
                ->comment('部署合约的参数');
            $table->text('bytecode')
                ->nullable()
                ->comment('合约的BIN内容');
            $table->longText('abi')
                ->nullable()
                ->comment('合约ABI内容');
            $table->longText('original')
                ->nullable()
                ->comment('合约源代码');
            $table->text('remark')
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
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('chain_addresses');
        Schema::dropIfExists('networks');
    }
};
