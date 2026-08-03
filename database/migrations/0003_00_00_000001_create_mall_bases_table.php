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
        Schema::create('banners', static function (Blueprint $table) {
            $table->comment('横幅广告表');
            $table->id();
            $table->tenant();
            $table->cover();
            $table->string('title')
                ->comment('横幅标题');
            $table->string('jump')
                ->nullable()
                ->comment('跳转链接');
            $table->sort();
            $table->easyStatus();
            $table->timestamps();
            $table->softDeletes()
                ->index();

            $table->index(['created_at']);
            $table->index(['status', 'sort']);
        });

        Schema::create('brands', static function (Blueprint $table) {
            $table->comment('品牌表');
            $table->id();
            $table->tenant();
            $table->string('name')
                ->comment('品牌名称');
            $table->string('description')
                ->nullable()
                ->comment('品牌描述');
            $table->cover();
            $table->sort();
            $table->jsonb('ext')
                ->nullable()
                ->comment('扩展信息');
            $table->easyStatus();
            $table->timestamps();
            $table->softDeletes()
                ->index();
        });

        Schema::create('expresses', static function (Blueprint $table) {
            $table->comment('快递公司表');
            $table->id();
            $table->string('name')
                ->comment('快递公司名称');
            $table->string('code')
                ->nullable()
                ->comment('快递公司代码');
            $table->string('phone', 32)
                ->nullable()
                ->comment('联系电话');
            $table->cover();
            $table->sort();
            $table->easyStatus();
            $table->timestamps();
            $table->softDeletes()
                ->index();
        });

        Schema::create('return_addresses', static function (Blueprint $table) {
            $table->comment('店铺退货地址');
            $table->id();
            $table->tenant();
            $table->string('name')
                ->comment('收货人姓名');
            $table->string('phone', 32)
                ->comment('联系电话');
            $table->regionAddress();
            $table->boolean('is_default')
                ->default(false)
                ->index()
                ->comment('是否默认地址');
            $table->boolean('status')
                ->default(true)
                ->comment('状态');
            $table->string('remark')
                ->nullable()
                ->comment('备注');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('store_configures', static function (Blueprint $table) {
            $table->comment('店铺配置');
            $table->unsignedBigInteger('tenant_id')
                ->primary()
                ->comment('所属租户');
            $table->cover()
                ->comment('店铺LOGO');
            $table->string('store_name')
                ->comment('店铺名称');
            $table->string('store_description')
                ->nullable()
                ->comment('店铺描述');
            $table->regionAddress();
            $table->string('phone', 32)
                ->nullable()
                ->comment('联系电话');
            $table->string('contactor')
                ->nullable()
                ->comment('联系人');
            $table->unsignedBigInteger('default_express_id')
                ->index()
                ->nullable()
                ->comment('默认发货快递公司ID');
            $table->unsignedTinyInteger('auto_complete_days')
                ->index()
                ->default(7)
                ->comment('自动完成天数');
            $table->unsignedTinyInteger('order_expired_minutes')
                ->index()
                ->default(60)
                ->comment('订单过期分钟数');
            $table->boolean('enabled')
                ->default(false)
                ->comment('商城是否已开通（总开关，审核通过后置为 true）');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_configures');
        Schema::dropIfExists('return_addresses');
        Schema::dropIfExists('expresses');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('banners');
    }
};
