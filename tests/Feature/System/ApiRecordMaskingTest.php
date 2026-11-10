<?php

namespace Tests\Feature\System;

use App\Http\Middleware\ApiRecord;
use App\Models\System\ApiLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class ApiRecordMaskingTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_and_response_secrets_are_masked_before_logging(): void
    {
        $request = Request::create('/api/auth/password', 'POST', [
            'username' => 'admin',
            'password' => 'plain-password',
            'payment_password' => 'pay-password',
        ]);

        $response = new Response(json_encode([
            'code' => 0,
            'data' => [
                'access_token' => 'token-value',
                'user' => ['name' => 'admin', 'app_secret' => 'secret-value'],
            ],
        ], JSON_THROW_ON_ERROR), 200, ['Content-Type' => 'application/json']);

        app(ApiRecord::class)->handle($request, static fn (): Response => $response);

        $log = ApiLog::query()->firstOrFail();

        $this->assertStringContainsString('admin', $log->input);
        $this->assertStringNotContainsString('plain-password', $log->input);
        $this->assertStringNotContainsString('pay-password', $log->input);

        $this->assertStringContainsString('admin', $log->output);
        $this->assertStringNotContainsString('token-value', $log->output);
        $this->assertStringNotContainsString('secret-value', $log->output);
    }

    public function test_non_json_response_is_logged_as_is(): void
    {
        $request = Request::create('/up', 'GET');

        $response = new Response('OK', 200);

        app(ApiRecord::class)->handle($request, static fn (): Response => $response);

        $this->assertSame('OK', ApiLog::query()->firstOrFail()->output);
    }
}
