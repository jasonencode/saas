<?php

namespace Tests\Unit\Support\Tenant;

use App\Support\Tenant\AppCredentialGenerator;
use PHPUnit\Framework\TestCase;

class AppCredentialGeneratorTest extends TestCase
{
    public function test_generates_16_chars_key_starting_with_one(): void
    {
        $key = AppCredentialGenerator::appKey();

        $this->assertSame(16, strlen($key));
        $this->assertStringStartsWith('1', $key);
        $this->assertMatchesRegularExpression('/^[1-9A-HJ-NP-Za-km-z]{16}$/', $key);
        $this->assertDoesNotMatchRegularExpression('/[0OIl]/', $key);
    }

    public function test_generates_unique_keys(): void
    {
        $this->assertNotSame(AppCredentialGenerator::appKey(), AppCredentialGenerator::appKey());
    }

    public function test_generates_32_chars_hex_secret(): void
    {
        $secret = AppCredentialGenerator::appSecret();

        $this->assertSame(32, strlen($secret));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $secret);
    }

    public function test_generates_unique_secrets(): void
    {
        $this->assertNotSame(AppCredentialGenerator::appSecret(), AppCredentialGenerator::appSecret());
    }
}
