<?php

use App\Enums\ActivityType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('activities', static function(Blueprint $table) {
            $table->id();
            $table->tenant();
            $table->enum('log_name', ActivityType::values())
                ->nullable();
            $table->text('description');
            $table->nullableMorphs('subject', 'subject');
            $table->string('event')
                ->nullable();
            $table->nullableMorphs('causer', 'causer');
            $table->json('properties')
                ->nullable();
            $table->uuid('batch_uuid')
                ->nullable();
            $table->boolean('is_audit')
                ->default(false);
            $table->unsignedBigInteger('auditor_id')
                ->nullable()
                ->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
