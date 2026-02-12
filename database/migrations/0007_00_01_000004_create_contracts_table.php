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
    }
};
