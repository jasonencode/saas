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
        Schema::create('redpacks', static function (Blueprint $table) {
            $table->comment('红包活动');
            $table->id();
            $table->tenant();
            $table->string('name')
                ->comment('活动名称');
            $table->string('description')
                ->nullable()
                ->comment('活动描述');
            $table->timestamp('start_at')
                ->nullable()
                ->comment('活动开始时间');
            $table->timestamp('end_at')
                ->nullable()
                ->comment('活动结束时间');
            $table->easyStatus();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('redpack_codes', static function (Blueprint $table) {
            $table->comment('红包码');
            $table->id();
            $table->foreignId('redpack_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('code', 64)
                ->unique()
                ->comment('唯一红包码');
            $table->decimal('amount')
                ->unsigned()
                ->comment('该码对应的红包金额（分）');
            $table->string('status', 16)
                ->index()
                ->default('active')
                ->comment('active:待领取, claimed:已领取, expired:过期, disabled:禁用');
            // 领取信息（也可通过关联表记录，但简单场景可直接冗余）
            $table->foreignId('user_id')
                ->nullable()
                ->index()
                ->comment('所属用户')
                ->constrained()
                ->cascadeOnDelete();
            $table->timestamp('claimed_at')
                ->nullable();
            $table->ipAddress('claimed_ip')
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
        Schema::dropIfExists('redpack_codes');
        Schema::dropIfExists('redpacks');
    }
};
