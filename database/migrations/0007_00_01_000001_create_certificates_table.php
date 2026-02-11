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
        Schema::create('certificates', static function (Blueprint $table) {
            $table->comment('证书管理表');
            $table->id();
            $table->tenant();
            $table->string('type', 16)
                ->index()
                ->comment('类型');
            $table->unsignedBigInteger('parent_id')
                ->index()
                ->nullable()
                ->comment('父级ID');
            $table->string('country_name')
                ->comment('国家');
            $table->string('state_or_province_name')
                ->comment('省份');
            $table->string('locality_name')
                ->comment('城市');
            $table->string('organization_name')
                ->comment('组织/公司');
            $table->string('organizational_unit_name')
                ->comment('部门');
            $table->string('common_name')
                ->comment('通用名称/域名');
            $table->string('email_address')
                ->nullable()
                ->comment('邮箱');

            $table->text('csr')
                ->nullable()
                ->comment('证书请求文件');
            $table->text('certificate')
                ->nullable()
                ->comment('证书内容');
            $table->text('private_key')
                ->nullable()
                ->comment('私钥');
            $table->string('password')
                ->nullable()
                ->comment('私钥密码');
            $table->unsignedInteger('days')
                ->default(0)
                ->comment('有效期(天)');
            $table->string('sign_type', 32)
                ->index()
                ->comment('签名类型');
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
        Schema::dropIfExists('certificates');
    }
};
