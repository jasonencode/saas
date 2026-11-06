<?php

use App\Enums\Campaign\SeckillActivityStatus;
use App\Enums\Campaign\SeckillRecordStatus;
use App\Enums\Campaign\SeckillSessionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('seckill_activities', static function (Blueprint $table) {
            $table->comment('秒杀活动');
            $table->id();
            $table->tenant();
            $table->string('name', 64)->comment('活动名称');
            $table->string('description')->nullable()->comment('活动说明');
            $table->string('cover')->nullable()->comment('活动封面');
            $table->string('status', 16)
                ->index()
                ->default(SeckillActivityStatus::Draft->value)
                ->comment('活动状态');
            $table->unsignedInteger('sort')->default(0)->comment('排序');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status', 'sort']);
        });

        Schema::create('seckill_sessions', static function (Blueprint $table) {
            $table->comment('秒杀场次');
            $table->id();
            $table->tenant();
            $table->unsignedBigInteger('activity_id')->index()->comment('活动ID');
            $table->string('name', 64)->comment('场次名称');
            $table->unsignedBigInteger('product_id')->index()->comment('商品ID');
            $table->unsignedBigInteger('sku_id')->index()->comment('参与秒杀的 SKU');
            $table->decimal('seckill_price', 12)
                ->unsigned()
                ->comment('秒杀价');
            $table->unsignedInteger('stock')->comment('秒杀名额');
            $table->unsignedInteger('sold')->default(0)->comment('已售数量');
            $table->unsignedInteger('limit_per_user')->default(1)->comment('每人限购');
            $table->timestamp('start_at')->index()->comment('开抢时间');
            $table->timestamp('end_at')->index()->comment('结束时间');
            $table->string('status', 16)
                ->index()
                ->default(SeckillSessionStatus::Scheduled->value)
                ->comment('场次状态');
            $table->unsignedInteger('sort')->default(0)->comment('排序');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status', 'start_at']);
            $table->index(['sku_id', 'start_at']);
        });

        Schema::create('seckill_records', static function (Blueprint $table) {
            $table->comment('秒杀资格与成交记录');
            $table->id();
            $table->tenant();
            $table->unsignedBigInteger('activity_id')->index()->comment('活动ID');
            $table->unsignedBigInteger('session_id')->index()->comment('场次ID');
            $table->user();
            $table->unsignedBigInteger('order_id')->nullable()->index()->comment('订单ID');
            $table->unsignedInteger('qty')->comment('数量');
            $table->decimal('price', 12)
                ->unsigned()
                ->comment('成交单价');
            $table->string('status', 16)
                ->index()
                ->default(SeckillRecordStatus::Reserved->value)
                ->comment('记录状态');
            $table->timestamps();

            $table->unique(['order_id', 'session_id']);
            $table->index(['user_id', 'session_id', 'status']);
        });

        Schema::table('orders', static function (Blueprint $table) {
            $table->string('source', 16)
                ->default('normal')
                ->index()
                ->comment('订单来源：normal/seckill');
        });

        Schema::table('order_items', static function (Blueprint $table) {
            $table->unsignedBigInteger('seckill_session_id')
                ->nullable()
                ->index()
                ->comment('秒杀场次ID');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', static function (Blueprint $table) {
            $table->dropColumn('seckill_session_id');
        });

        Schema::table('orders', static function (Blueprint $table) {
            $table->dropColumn('source');
        });

        Schema::dropIfExists('seckill_records');
        Schema::dropIfExists('seckill_sessions');
        Schema::dropIfExists('seckill_activities');
    }
};
