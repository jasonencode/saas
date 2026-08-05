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
        Schema::create('invoice_titles', static function (Blueprint $table) {
            $table->comment('发票抬头表');
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

        // 发票申请表
        Schema::create('invoice_applications', static function (Blueprint $table) {
            $table->comment('发票申请表');
            $table->id();
            $table->user();
            $table->tenant();
            $table->unsignedBigInteger('invoice_title_id')
                ->index()
                ->comment('发票抬头ID');
            $table->json('title_snapshot')
                ->nullable()
                ->comment('发票抬头快照（申请提交时冻结）');
            $table->decimal('amount', 12)
                ->unsigned()
                ->comment('开票金额');
            $table->string('reason', 255)
                ->comment('开票原因');
            $table->string('status', 16)
                ->default('pending')
                ->comment('申请状态：pending=待处理，approved=已批准，rejected=已拒绝');
            $table->text('remark')
                ->nullable()
                ->comment('备注');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
        });

        // 发票申请-订单关联表
        Schema::create('invoice_application_order', static function (Blueprint $table) {
            $table->comment('发票申请-订单关联表');
            $table->id();
            $table->unsignedBigInteger('invoice_application_id')
                ->index()
                ->comment('发票申请ID');
            $table->unsignedBigInteger('order_id')
                ->index()
                ->comment('订单ID');
            $table->timestamps();

            $table->unique(['invoice_application_id', 'order_id']);
        });

        // 发票表
        Schema::create('invoices', static function (Blueprint $table) {
            $table->comment('发票表');
            $table->id();
            $table->user();
            $table->tenant();
            $table->unsignedBigInteger('invoice_application_id')
                ->index()
                ->comment('发票申请ID');
            $table->unsignedBigInteger('invoice_title_id')
                ->nullable()
                ->index()
                ->comment('发票抬头ID');
            $table->json('title_snapshot')
                ->nullable()
                ->comment('发票抬头快照（开票时冻结）');
            $table->string('invoice_no', 32)
                ->unique()
                ->comment('发票号码');
            $table->date('invoice_date')
                ->comment('开票日期');
            $table->string('type', 16)
                ->comment('发票类型：普通发票，增值税发票');
            $table->decimal('amount', 12)
                ->unsigned()
                ->comment('发票金额');
            $table->string('status', 16)
                ->default('issued')
                ->index()
                ->comment('发票状态：issued=已开具，sent=已发送');
            $table->string('recipient_email', 128)
                ->nullable()
                ->comment('接收邮箱');
            $table->string('recipient_phone', 32)
                ->nullable()
                ->comment('接收电话');
            $table->text('remark')
                ->nullable()
                ->comment('备注');
            $table->string('creator', 32)
                ->comment('开票人');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('invoice_application_order');
        Schema::dropIfExists('invoice_applications');
        Schema::dropIfExists('invoice_titles');
    }
};
