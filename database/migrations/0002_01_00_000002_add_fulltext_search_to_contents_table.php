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

        Schema::table('contents', static function (Blueprint $table) {
            $table->tsvector('search_vector')
                ->storedAs(DB::raw(
                    "setweight(to_tsvector('simple', coalesce(title, '')), 'A') || ".
                    "setweight(to_tsvector('simple', coalesce(sub_title, '')), 'B') || ".
                    "setweight(to_tsvector('simple', coalesce(description, '')), 'C') || ".
                    "setweight(to_tsvector('simple', coalesce(content, '')), 'D')"
                ))
                ->comment('全文搜索向量');
        });

        DB::statement('CREATE INDEX contents_search_vector_idx ON contents USING GIN(search_vector)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        Schema::table('contents', static function (Blueprint $table) {
            $table->dropIndex('contents_search_vector_idx');
            $table->dropColumn('search_vector');
        });
    }
};
