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
        Schema::create('pickup_points', static function (Blueprint $table) {
            $table->comment('自提点/门店');
            $table->id();
            $table->tenant();
            $table->string('name')
                ->comment('自提点名称');
            $table->string('contact')
                ->nullable()
                ->comment('联系人');
            $table->string('phone', 32)
                ->nullable()
                ->comment('联系电话');
            $table->regionAddress();
            $table->string('remark')
                ->nullable()
                ->comment('备注');
            $table->easyStatus();
            $table->sort();
            $table->timestamps();
            $table->softDeletes()
                ->index();
            $table->index(['tenant_id', 'status', 'sort']);
            $table->index('created_at');
        });

        Schema::create('pickup_point_product', static function (Blueprint $table) {
            $table->comment('自提点商品关联表');
            $table->id();
            $table->unsignedBigInteger('pickup_point_id')
                ->comment('自提点ID');
            $table->unsignedBigInteger('product_id')
                ->comment('商品ID');
            $table->timestamps();

            $table->unique(['pickup_point_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pickup_point_product');
        Schema::dropIfExists('pickup_points');
    }
};
