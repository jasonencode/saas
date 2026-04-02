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
        Schema::create('user_realnames', static function (Blueprint $table) {
            $table->id();
            $table->user();
            $table->tenant();
            $table->string('type', 16)
                ->comment('认证类型：personal=个人，enterprise=企业');
            $table->string('status', 16)
                ->default('pending')
                ->comment('审核状态：pending=待审核，approved=已认证，rejected=已拒绝');
            $table->string('name')
                ->comment('真实姓名/企业名称');
            $table->string('id_card_number', 32)
                ->nullable()
                ->comment('身份证号码（个人）');
            $table->string('id_card_frontl')
                ->nullable()
                ->comment('身份证正面照');
            $table->string('id_card_back')
                ->nullable()
                ->comment('身份证背面照');
            $table->string('business_license')
                ->nullable()
                ->comment('营业执照（企业）');
            $table->string('contact_person', 32)
                ->nullable()
                ->comment('联系人（企业）');
            $table->string('contact_phone', 20)
                ->nullable()
                ->comment('联系电话');
            $table->string('reject_reason', 500)
                ->nullable()
                ->comment('拒绝原因');
            $table->timestamp('verified_at')
                ->nullable()
                ->comment('认证通过时间');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'type']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_realnames');
    }
};
