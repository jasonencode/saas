<?php

namespace Tests\Feature\System;

use App\Models\Foundation\WechatPayment;
use App\Models\System\Tenant;
use App\Services\System\SecretEncryptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SecretEncryptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_app_secret_is_encrypted_at_rest_and_readable(): void
    {
        $tenant = Tenant::factory()->create(['app_secret' => 'tenant-secret']);

        $raw = DB::table('tenants')->where('id', $tenant->getKey())->value('app_secret');

        $this->assertNotSame('tenant-secret', $raw);
        $this->assertSame('tenant-secret', Crypt::decryptString($raw));
        $this->assertSame('tenant-secret', $tenant->fresh()->app_secret);
    }

    public function test_wechat_payment_secret_and_private_key_are_encrypted_at_rest(): void
    {
        $payment = WechatPayment::create([
            'wechat_id' => 1,
            'name' => '测试商户',
            'mch_id' => 'mch-1',
            'secret' => 'v3-secret',
            'private_key' => "-----BEGIN PRIVATE KEY-----\nabc\n-----END PRIVATE KEY-----",
        ]);

        $raw = DB::table('wechat_payments')->where('id', $payment->getKey())->first();

        $this->assertNotSame('v3-secret', $raw->secret);
        $this->assertStringNotContainsString('BEGIN PRIVATE KEY', (string) $raw->private_key);
        $this->assertSame('v3-secret', $payment->fresh()->secret);
        $this->assertStringContainsString('BEGIN PRIVATE KEY', $payment->fresh()->private_key);
    }

    public function test_encrypt_command_converts_legacy_plaintext_and_is_idempotent(): void
    {
        $tenant = Tenant::factory()->create();

        // 模拟上线前的存量明文（绕过模型 cast 直接写库）
        DB::table('tenants')->where('id', $tenant->getKey())->update(['app_secret' => 'legacy-secret']);

        $this->artisan('system:encrypt-secrets')->assertSuccessful();

        $encrypted = DB::table('tenants')->where('id', $tenant->getKey())->value('app_secret');

        $this->assertTrue(service(SecretEncryptionService::class)->isEncrypted($encrypted));
        $this->assertSame('legacy-secret', $tenant->fresh()->app_secret);

        // 重复执行不应再次加密
        $this->artisan('system:encrypt-secrets')->assertSuccessful();
        $this->assertSame($encrypted, DB::table('tenants')->where('id', $tenant->getKey())->value('app_secret'));

        // 解密命令可还原明文
        $this->artisan('system:decrypt-secrets')->assertSuccessful();
        $this->assertSame('legacy-secret', DB::table('tenants')->where('id', $tenant->getKey())->value('app_secret'));
    }

    public function test_null_secret_stays_null(): void
    {
        $tenant = Tenant::factory()->create(['app_secret' => null]);

        $this->assertNull($tenant->fresh()->app_secret);
    }
}
