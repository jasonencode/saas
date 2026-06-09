<?php

namespace Tests\Feature\Finance;

use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceApiTest extends TestCase
{
    use RefreshDatabase;

    // ─── Payments (requires auth) ─────────────────────────────────

    public function test_payments_requires_authentication(): void
    {
        $this->postJson('/api/payments')->assertUnauthorized();
        $this->getJson('/api/payments/1')->assertUnauthorized();
        $this->postJson('/api/payments/1/refund')->assertUnauthorized();
    }

    public function test_payment_requires_amount(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/payments', [
                'gateway' => 'alipay',
            ]);

        $response->assertStatus(422);
    }

    public function test_payment_requires_gateway(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/payments', [
                'amount' => 100,
            ]);

        $response->assertStatus(422);
    }

    // ─── Vouchers (requires auth) ─────────────────────────────────

    public function test_vouchers_requires_authentication(): void
    {
        $this->getJson('/api/vouchers')->assertUnauthorized();
    }

    public function test_can_list_vouchers(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/vouchers');

        $response->assertOk();
    }
}
