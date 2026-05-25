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
            $table->comment('区块链网络');
            $table->id();
            $table->tenant();
            $table->string('name')
                ->comment('网络名称');
            $table->string('type', 32)
                ->index()
                ->comment('网络类型');
            $table->string('rpc_url')
                ->nullable()
                ->comment('RPC地址');
            $table->string('explorer_url')
                ->nullable()
                ->comment('浏览器地址');
            $table->string('group_id')
                ->nullable()
                ->comment('FISCO BCOS 群组 ID');
            $table->text('ca_cert')
                ->nullable()
                ->comment('CA 证书（PEM）');
            $table->text('client_cert')
                ->nullable()
                ->comment('客户端证书（PEM）');
            $table->text('client_key')
                ->nullable()
                ->comment('客户端私钥（PEM）');
            $table->boolean('status')
                ->default(false)
                ->index()
                ->comment('状态');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('chain_addresses', static function (Blueprint $table) {
            $table->comment('链地址');
            $table->id();
            $table->tenant();
            $table->unsignedBigInteger('network_id')
                ->index()
                ->comment('网络ID');
            $table->string('name')
                ->nullable()
                ->comment('地址名称');
            $table->string('address', 64)
                ->comment('地址');
            $table->string('private_key')
                ->comment('私钥');
            $table->string('remark')
                ->nullable()
                ->comment('备注');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contracts', static function (Blueprint $table) {
            $table->comment('智能合约');
            $table->id();
            $table->tenant();
            $table->unsignedBigInteger('deployer_id')
                ->index()
                ->comment('部署者ID');
            $table->string('name')
                ->comment('合约名称');
            $table->string('address')
                ->nullable()
                ->comment('合约地址');
            $table->string('hash')
                ->nullable()
                ->comment('交易哈希');
            $table->string('parameter')
                ->nullable()
                ->comment('部署参数');
            $table->text('bytecode')
                ->nullable()
                ->comment('字节码');
            $table->longText('abi')
                ->nullable()
                ->comment('ABI');
            $table->longText('original')
                ->nullable()
                ->comment('源代码');
            $table->text('remark')
                ->nullable()
                ->comment('备注');
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
