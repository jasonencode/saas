<?php

namespace Tests\Feature\User;

use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SafeApiTest extends TestCase
{
    use RefreshDatabase;

    // ─── GET /api/user/safe/payment-password/status ──────────────

    public function test_payment_password_status_defaults_to_false(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $this->getJson('/api/user/safe/payment-password/status')
            ->assertOk()
            ->assertJsonPath('has_password', false);
    }

    public function test_payment_password_status_returns_true_when_set(): void
    {
        $user = $this->makeUser();
        $user->account->update(['payment_password' => '654321']);
        Sanctum::actingAs($user);

        $this->getJson('/api/user/safe/payment-password/status')
            ->assertOk()
            ->assertJsonPath('has_password', true);
    }

    // ─── POST /api/user/safe/payment-password ────────────────────

    public function test_can_set_payment_password(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/user/safe/payment-password', [
            'password' => '520520',
            're_password' => '520520',
        ])->assertNoContent();

        $user->refresh();

        $this->assertNotNull($user->account->payment_password);
        $this->assertTrue(Hash::check('520520', $user->account->payment_password));
    }

    public function test_set_payment_password_requires_confirmation_match(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/user/safe/payment-password', [
            'password' => '520520',
            're_password' => '123456',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('re_password');

        $this->assertNull($user->refresh()->account->payment_password);
    }

    public function test_set_payment_password_rejects_invalid_passwords(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $invalid = ['1234567', 'abc123', '111111', '123456', '789012', '987654'];

        foreach ($invalid as $password) {
            $this->postJson('/api/user/safe/payment-password', [
                'password' => $password,
                're_password' => $password,
            ])->assertUnprocessable()
                ->assertJsonValidationErrors('password');
        }

        $this->assertNull($user->refresh()->account->payment_password);
    }

    // ─── PUT /api/user/safe/payment-password ─────────────────────

    public function test_can_change_payment_password_with_correct_old_password(): void
    {
        $user = $this->makeUser();
        $user->account->update(['payment_password' => '520520']);
        Sanctum::actingAs($user);

        $this->putJson('/api/user/safe/payment-password', [
            'old_password' => '520520',
            'password' => '102938',
            're_password' => '102938',
        ])->assertNoContent();

        $this->assertTrue(Hash::check('102938', $user->refresh()->account->payment_password));
    }

    public function test_change_payment_password_fails_with_wrong_old_password(): void
    {
        $user = $this->makeUser();
        $user->account->update(['payment_password' => '520520']);
        Sanctum::actingAs($user);

        $this->putJson('/api/user/safe/payment-password', [
            'old_password' => '102938',
            'password' => '135790',
            're_password' => '135790',
        ])->assertStatus(500)
            ->assertJsonPath('code', 500);

        $this->assertTrue(Hash::check('520520', $user->refresh()->account->payment_password));
    }

    public function test_change_payment_password_requires_old_password(): void
    {
        $user = $this->makeUser();
        $user->account->update(['payment_password' => '520520']);
        Sanctum::actingAs($user);

        $this->putJson('/api/user/safe/payment-password', [
            'password' => '102938',
            're_password' => '102938',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('old_password');
    }

    // ─── POST /api/user/safe/logout ──────────────────────────────

    public function test_logout_revokes_current_access_token(): void
    {
        $user = $this->makeUser();
        $user->createToken('device-a');
        $token = $user->createToken('device-b');

        $this->postJson('/api/user/safe/logout', [], [
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->assertNoContent();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    // ─── GET /api/user/safe/records ──────────────────────────────

    public function test_can_list_login_records(): void
    {
        $user = $this->makeUser();
        $user->records()->create([
            'ip' => '127.0.0.1',
            'user_agent' => 'Test Agent',
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/user/safe/records')
            ->assertOk()
            ->assertJsonStructure(['list', 'page' => ['current', 'total']]);

        $this->assertCount(
            1,
            $user->records()->get(),
        );
    }

    // ─── 未登录 ──────────────────────────────────────────────────

    public function test_guest_cannot_access_safe_endpoints(): void
    {
        $this->getJson('/api/user/safe/records')->assertUnauthorized();
        $this->getJson('/api/user/safe/payment-password/status')->assertUnauthorized();
        $this->postJson('/api/user/safe/payment-password', [])->assertUnauthorized();
        $this->putJson('/api/user/safe/payment-password', [])->assertUnauthorized();
        $this->postJson('/api/user/safe/logout')->assertUnauthorized();
    }

    private function makeUser(string $username = 'safe-api-user', string $password = 'secret-password'): User
    {
        return User::create([
            'username' => $username,
            'password' => $password,
        ]);
    }
}
