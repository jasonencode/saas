<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        Schema::table('products', static function (Blueprint $table) {
            $table->tsvector('search_vector')
                ->storedAs(DB::raw(
                    "setweight(to_tsvector('simple', coalesce(name, '')), 'A') || ".
                    "setweight(to_tsvector('simple', coalesce(description, '')), 'B')"
                ))
                ->comment('全文搜索向量');
        });

        DB::statement('CREATE INDEX products_search_vector_idx ON products USING GIN(search_vector)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        Schema::table('products', static function (Blueprint $table) {
            $table->dropIndex('products_search_vector_idx');
            $table->dropColumn('search_vector');
        });
    }
};
