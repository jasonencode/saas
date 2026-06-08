<?php

use App\Enums\BlockChain\ContractDeployStatus;
use App\Enums\BlockChain\ContractType;
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
                ->comment('RPC 地址');
            $table->string('explorer_url')
                ->nullable()
                ->comment('浏览器地址');
            $table->json('config')
                ->nullable()
                ->comment('链配置，如 SSL 证书、平行链参数等');
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
                ->comment('网络 ID');
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
            $table->unsignedBigInteger('network_id')
                ->nullable()
                ->index()
                ->comment('网络 ID');
            $table->unsignedBigInteger('deployer_id')
                ->index()
                ->comment('部署者 ID');
            $table->string('name')
                ->comment('合约名称');
            $table->string('type', 16)
                ->index()
                ->default(ContractType::CUSTOM->value)
                ->comment('合约类型');
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
            $table->string('deploy_status', 16)
                ->index()
                ->default(ContractDeployStatus::Pending->value)
                ->comment('部署状态');
            $table->text('remark')
                ->nullable()
                ->comment('备注');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contract_repositories', static function (Blueprint $table) {
            $table->comment('智能合约仓库');
            $table->id();
            $table->string('name')
                ->index()
                ->comment('合约名称');
            $table->string('slug')
                ->unique()
                ->comment('合约唯一标识');
            $table->string('version', 32)
                ->default('1.0.0')
                ->comment('版本号');
            $table->string('compiler_version', 32)
                ->nullable()
                ->comment('Solidity 编译器版本');
            $table->string('license', 64)
                ->nullable()
                ->comment('开源协议');
            $table->string('contract_name')
                ->nullable()
                ->comment('主合约名');
            $table->string('source_path')
                ->nullable()
                ->comment('源文件存储路径');
            $table->string('source_name')
                ->nullable()
                ->comment('源文件名称');
            $table->unsignedBigInteger('source_size')
                ->default(0)
                ->comment('源文件大小');
            $table->longText('source_code')
                ->nullable()
                ->comment('Sol 源码内容');
            $table->longText('abi')
                ->nullable()
                ->comment('编译后的 ABI');
            $table->longText('bytecode')
                ->nullable()
                ->comment('编译后的字节码');
            $table->text('description')
                ->nullable()
                ->comment('描述');
            $table->json('tags')
                ->nullable()
                ->comment('标签');
            $table->boolean('status')
                ->default(true)
                ->index()
                ->comment('状态');
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
        Schema::dropIfExists('contract_repositories');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('chain_addresses');
        Schema::dropIfExists('networks');
    }
};
