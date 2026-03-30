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
        Schema::create('identities', static function (Blueprint $table) {
            $table->comment('用户身份/等级');
            $table->id();
            $table->tenant();
            $table->string('name')
                ->comment('身份名称');
            $table->string('description')
                ->nullable()
                ->comment('简介');
            $table->cover();
            $table->decimal('price')
                ->unsigned()
                ->default(0)
                ->comment('订阅价格');
            $table->sort();
            $table->easyStatus();
            $table->boolean('is_default')
                ->default(false)
                ->comment('是否默认身份');
            $table->boolean('is_unique')
                ->default(false)
                ->comment('是否是唯一身份，订阅后不允许订阅其他身份');
            $table->boolean('can_subscribe')
                ->default(false)
                ->comment('是否可订阅');
            $table->unsignedInteger('days')
                ->default(0)
                ->comment('有效期（天）');
            $table->boolean('serial_open')
                ->default(false)
                ->comment('是否开启身份编号');
            $table->unsignedTinyInteger('serial_places')
                ->default(0)
                ->comment('身份编号位数');
            $table->unsignedInteger('serial_reserve')
                ->default(0)
                ->comment('预留编号数量');
            $table->string('serial_prefix', 16)
                ->nullable()
                ->comment('身份编号前缀');
            $table->jsonb('conditions')
                ->nullable()
                ->comment('升级条件');
            $table->jsonb('rules')
                ->nullable()
                ->comment('身份权益');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('user_identity', static function (Blueprint $table) {
            $table->comment('用户身份中间表');
            $table->user();
            $table->foreignId('identity_id')
                ->index()
                ->constrained()
                ->cascadeOnDelete();
            $table->dateTime('start_at')
                ->nullable()
                ->comment('身份的开始时间');
            $table->dateTime('end_at')
                ->nullable()
                ->comment('角色的结束时间');
            $table->string('serial')
                ->nullable()
                ->comment('身份生成的编号');
            $table->timestamps();

            $table->primary(['user_id', 'identity_id']);
        });

        Schema::create('identity_logs', static function (Blueprint $table) {
            $table->id();
            $table->user();
            $table->unsignedBigInteger('before')
                ->comment('变化前身份');
            $table->unsignedBigInteger('after')
                ->comment('变化后身份');
            $table->string('channel', 16)
                ->index()
                ->comment('变化渠道');
            $table->json('source')
                ->nullable()
                ->comment('附加溯源信息');
            $table->timestamps();
        });

        Schema::create('identity_orders', static function (Blueprint $table) {
            $table->comment('身份订阅订单');
            $table->id();
            $table->tenant();
            $table->no();
            $table->user();
            $table->foreignId('identity_id')
                ->index()
                ->constrained()
                ->cascadeOnDelete();
            $table->unsignedBigInteger('qty')
                ->comment('订阅数量');
            $table->decimal('amount')
                ->unsigned()
                ->comment('订单总金额');
            $table->string('status', 16)
                ->index()
                ->comment('支付状态');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('identity_orders');
        Schema::dropIfExists('identity_logs');
        Schema::dropIfExists('user_identity');
        Schema::dropIfExists('identities');
    }
};
