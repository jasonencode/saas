<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoice_titles', static function (Blueprint $table) {
            $table->id();
            $table->user();
            $table->tenant();
            $table->string('type', 16)
                ->comment('抬头类型：personal=个人，enterprise=企业');
            $table->string('title')
                ->comment('发票抬头名称（个人姓名/企业名称）');
            $table->string('tax_no', 64)
                ->nullable()
                ->comment('纳税人识别号（企业编号/身份证）');
            $table->string('company_address', 255)
                ->nullable()
                ->comment('企业地址');
            $table->string('company_phone', 32)
                ->nullable()
                ->comment('企业电话');
            $table->string('bank_name', 64)
                ->nullable()
                ->comment('开户行');
            $table->string('bank_account', 64)
                ->nullable()
                ->comment('银行账号');
            $table->string('email', 128)
                ->nullable()
                ->comment('接收邮箱');
            $table->boolean('is_default')
                ->default(false)
                ->index()
                ->comment('是否默认抬头');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_titles');
    }
};
