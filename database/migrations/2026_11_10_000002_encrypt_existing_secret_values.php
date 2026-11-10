<?php

use App\Services\System\SecretEncryptionService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * 把存量明文密钥一次性加密回写；已加密的记录会被跳过，可安全重复执行。
     */
    public function up(): void
    {
        service(SecretEncryptionService::class)->encryptAll();
    }

    /**
     * Reverse the migrations.
     *
     * 解密会破坏"落库即密文"的约定，回滚请手动执行 `php artisan system:decrypt-secrets`
     */
    public function down(): void
    {
        //
    }
};
