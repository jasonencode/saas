<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 修复 payment_refunds 历史库缺失列
 *
 * 部分环境在支付退款闭环功能上线前已执行过 create_payments_table，
 * 后续直接修改原迁移文件不会重新执行，导致这些环境缺少
 * failed_reason / channel_refund_no / source_type / source_id。
 *
 * 本迁移按列检测补齐，已存在的环境不会重复添加，可安全重复部署。
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $columns = collect(Schema::getColumns('payment_refunds'))->keyBy('name');

        if (!isset($columns['failed_reason'])) {
            Schema::table('payment_refunds', static function (Blueprint $table) {
                $table->string('failed_reason')
                    ->nullable()
                    ->comment('退款失败原因');
            });
        }

        if (!isset($columns['channel_refund_no'])) {
            Schema::table('payment_refunds', static function (Blueprint $table) {
                $table->string('channel_refund_no', 64)
                    ->nullable()
                    ->comment('支付通道退款单号（如微信 refund_id）');
            });
        }

        if (!isset($columns['source_type'])) {
            Schema::table('payment_refunds', static function (Blueprint $table) {
                $table->string('source_type')
                    ->nullable()
                    ->comment('退款来源单据类型');
            });
        }

        if (!isset($columns['source_id'])) {
            Schema::table('payment_refunds', static function (Blueprint $table) {
                $table->unsignedBigInteger('source_id')
                    ->nullable()
                    ->comment('退款来源单据ID');
            });
        }

        // 系统自动发起退款时创建人可为空
        if (isset($columns['created_by_id']) && !$columns['created_by_id']['nullable']) {
            Schema::table('payment_refunds', static function (Blueprint $table) {
                $table->unsignedBigInteger('created_by_id')->nullable()->change();
            });
        }

        if (isset($columns['created_by_type']) && !$columns['created_by_type']['nullable']) {
            Schema::table('payment_refunds', static function (Blueprint $table) {
                $table->string('created_by_type')->nullable()->change();
            });
        }

        if (!Schema::hasIndex('payment_refunds', ['source_type', 'source_id'])) {
            Schema::table('payment_refunds', static function (Blueprint $table) {
                $table->index(['source_type', 'source_id'], 'payment_refunds_source_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 修复型迁移不反向删除补齐的列：这些列属于支付退款闭环的基础结构，
        // 新环境由原始迁移创建，旧环境补齐后也应保留。
        if (Schema::hasIndex('payment_refunds', 'payment_refunds_source_index')) {
            Schema::table('payment_refunds', static function (Blueprint $table) {
                $table->dropIndex('payment_refunds_source_index');
            });
        }
    }
};
