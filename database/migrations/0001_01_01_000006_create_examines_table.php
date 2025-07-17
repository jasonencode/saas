<?php

use App\Enums\ExamineState;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('examines', static function(Blueprint $table) {
            $table->id();
            $table->morphs('target');
            $table->enum('state', ExamineState::values())
                ->index()
                ->default(ExamineState::Pending);
            $table->string('pending_text')
                ->comment('申请说明')
                ->nullable();
            $table->string('reject_text')
                ->nullable()
                ->comment('拒绝原因');
            $table->string('pass_text')
                ->nullable()
                ->comment('通过备注');
            $table->string('reviewer_type')
                ->comment('审核人')
                ->nullable();
            $table->unsignedBigInteger('reviewer_id')
                ->comment('审核人')
                ->nullable();
            $table->dateTime('passed_at')
                ->nullable()
                ->comment('通过时间');
            $table->dateTime('rejected_at')
                ->nullable()
                ->comment('驳回时间');

            $table->index(['reviewer_type', 'reviewer_id']);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('examines');
    }
};
