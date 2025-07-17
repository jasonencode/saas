<?php

use App\Enums\SmsChannel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sms_codes', static function(Blueprint $table) {
            $table->id();
            $table->string('phone', 16)
                ->index();
            $table->enum('channel', SmsChannel::values())
                ->index();
            $table->string('gateway', 16)
                ->default('debug');
            $table->string('code', 6);
            $table->boolean('used')
                ->default(0);
            $table->dateTime('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_codes');
    }
};
