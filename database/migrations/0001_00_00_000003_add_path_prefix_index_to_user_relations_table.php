<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (config('database.default') === 'pgsql') {
            DB::statement('CREATE INDEX idx_user_relations_path_prefix ON user_relations (path text_pattern_ops)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
