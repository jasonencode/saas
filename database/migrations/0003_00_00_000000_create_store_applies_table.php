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
        Schema::create('store_applies', static function (Blueprint $table) {
            $table->comment('店铺申请');
            $table->id();
            $table->tenant();
            $table->string('store_name')
                ->comment('店铺名称');
            $table->string('store_description')
                ->comment('店铺描述');
            $table->string('contactor')
                ->comment('联系人');
            $table->string('phone')
                ->comment('联系电话');
            $table->string('front')
                ->comment('身份证正面（国徽面）');
            $table->string('back')
                ->comment('身份证背面（人像面）');
            $table->string('license')
                ->comment('企业营业执照');
            $table->string('status', 16)
                ->index()
                ->comment('状态：枚举类型');
            $table->json('ext')
                ->nullable()
                ->comment('扩展信息');
            $table->string('reason')
                ->nullable()
                ->comment('拒绝理由');
            $table->string('remark')
                ->nullable()
                ->comment('审核备注');
            $table->nullableMorphs('approver');
            $table->timestamps();

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_applies');
    }
};
