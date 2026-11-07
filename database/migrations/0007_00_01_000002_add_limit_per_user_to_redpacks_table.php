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
        Schema::table('redpacks', static function (Blueprint $table) {
            $table->unsignedInteger('limit_per_user')
                ->nullable()
                ->after('status')
                ->comment('每人最多领取数量，为空表示不限制');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('redpacks', static function (Blueprint $table) {
            $table->dropColumn('limit_per_user');
        });
    }
};
