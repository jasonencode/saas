<?php

namespace Tests\Feature\User;

use App\Enums\Finance\AccountAssetType;
use App\Enums\Finance\InvoiceTitleType;
use App\Enums\Mall\RegionLevel;
use App\Enums\User\Gender;
use App\Enums\User\UserAccountLogType;
use App\Models\Finance\UserAccountLog;
use App\Models\User\Address;
use App\Models\User\User;
use App\Notifications\DemoNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_user_module(): void
    {
        $response = $this->getJson('/api/user/profile');

        $response->assertUnauthorized()
            ->assertJsonPath('code', 401);
    }

    public function test_authenticated_user_can_view_and_update_profile(): void
    {
        $user = $this->makeUser('profile-user', 'old-secret');
        Sanctum::actingAs($user);

        $this->getJson('/api/user/profile')
            ->assertOk()
            ->assertJsonPath('user_id', $user->id)
            ->assertJsonPath('username', 'profile-user');

        $this->putJson('/api/user/profile', [
            'nickname' => 'Profile Tester',
            'gender' => Gender::Female->value,
            'birthday' => '1994-06-12',
        ])->assertOk()
            ->assertJsonPath('profile.nickname', 'Profile Tester')
            ->assertJsonPath('profile.gender.value', Gender::Female->value)
            ->assertJsonPath('profile.birthday', '1994-06-12');

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'nickname' => 'Profile Tester',
            'gender' => Gender::Female->value,
            'birthday' => '1994-06-12 00:00:00',
        ]);
    }

    public function test_account_summary_and_logs_are_available(): void
    {
        $user = $this->makeUser('account-user');
        Sanctum::actingAs($user);

        $user->account()->update([
            'balance' => 125.50,
            'frozen_balance' => 10,
            'points' => 88,
            'frozen_points' => 3,
        ]);

        UserAccountLog::create([
            'user_id' => $user->id,
            'type' => UserAccountLogType::System,
            'asset' => AccountAssetType::Balance,
            'amount' => 25,
            'before' => 100,
            'after' => 125,
            'remark' => 'manual adjustment',
        ]);

        $this->getJson('/api/user/account')
            ->assertOk()
            ->assertJsonPath('balance', '125.50')
            ->assertJsonPath('frozen_balance', '10.00')
            ->assertJsonPath('points', '88.00')
            ->assertJsonPath('frozen_points', '3.00');

        $this->getJson('/api/user/account/logs')
            ->assertOk()
            ->assertJsonPath('data.0.remark', 'manual adjustment');
    }

    public function test_user_can_manage_addresses(): void
    {
        [$provinceId, $cityId, $districtId] = $this->makeRegions();
        $user = $this->makeUser('address-user');
        Sanctum::actingAs($user);

        $payload = [
            'name' => 'Receiver One',
            'mobile' => '13800138000',
            'province_id' => $provinceId,
            'city_id' => $cityId,
            'district_id' => $districtId,
            'address' => 'First test street',
            'is_default' => true,
        ];

        $addressId = $this->postJson('/api/user/addresses', $payload)
            ->assertCreated()
            ->assertJsonPath('name', 'Receiver One')
            ->assertJsonPath('is_default', true)
            ->json('address_id');

        $this->assertDatabaseHas('addresses', [
            'id' => $addressId,
            'user_id' => $user->id,
            'is_default' => true,
        ]);

        $secondAddress = Address::create([
            'user_id' => $user->id,
            'name' => 'Receiver Two',
            'mobile' => '13900139000',
            'province_id' => $provinceId,
            'city_id' => $cityId,
            'district_id' => $districtId,
            'address' => 'Second test street',
            'is_default' => false,
        ]);

        $this->getJson('/api/user/addresses/'.$addressId)
            ->assertOk()
            ->assertJsonPath('address_id', $addressId);

        $this->putJson('/api/user/addresses/'.$addressId, [
            ...$payload,
            'name' => 'Receiver Updated',
            'address' => 'Updated test street',
        ])->assertOk()
            ->assertJsonPath('name', 'Receiver Updated')
            ->assertJsonPath('address', 'Updated test street');

        $this->putJson('/api/user/addresses/'.$secondAddress->id.'/default')
            ->assertNoContent();

        $this->assertDatabaseHas('addresses', [
            'id' => $secondAddress->id,
            'is_default' => true,
        ]);
        $this->assertDatabaseHas('addresses', [
            'id' => $addressId,
            'is_default' => false,
        ]);

        $this->deleteJson('/api/user/addresses/'.$addressId)
            ->assertNoContent();

        $this->assertSoftDeleted('addresses', [
            'id' => $addressId,
        ]);
    }

    public function test_user_cannot_access_another_users_address(): void
    {
        [$provinceId, $cityId, $districtId] = $this->makeRegions();
        $owner = $this->makeUser('address-owner');
        $user = $this->makeUser('address-intruder');
        $address = Address::create([
            'user_id' => $owner->id,
            'name' => 'Owner Receiver',
            'mobile' => '13800138000',
            'province_id' => $provinceId,
            'city_id' => $cityId,
            'district_id' => $districtId,
            'address' => 'Owner street',
            'is_default' => true,
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/user/addresses/'.$address->id)
            ->assertForbidden();
    }

    public function test_user_can_read_and_delete_notifications(): void
    {
        $user = $this->makeUser('notification-user');
        Sanctum::actingAs($user);

        $unread = $this->makeNotification($user, read: false);
        $this->makeNotification($user, read: true);

        $this->getJson('/api/user/notifications/count')
            ->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonPath('unread', 1);

        $this->getJson('/api/user/notifications/'.$unread)
            ->assertOk()
            ->assertJsonPath('notification_id', $unread)
            ->assertJsonPath('read', true);

        $this->assertDatabaseMissing('notifications', [
            'id' => $unread,
            'read_at' => null,
        ]);

        $this->deleteJson('/api/user/notifications/read')
            ->assertNoContent();

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_user_can_change_password_with_current_password(): void
    {
        $user = $this->makeUser('safe-user', 'old-secret');
        Sanctum::actingAs($user);

        $this->putJson('/api/user/safe/password', [
            'old_pass' => 'wrong-secret',
            'new_pass' => 'new-secret',
            're_pass' => 'new-secret',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('old_pass');

        $this->putJson('/api/user/safe/password', [
            'old_pass' => 'old-secret',
            'new_pass' => 'new-secret',
            're_pass' => 'new-secret',
        ])->assertNoContent();

        $this->assertTrue(Hash::check('new-secret', $user->refresh()->password));
    }

    public function test_invoice_title_creation_uses_database_columns_consistently(): void
    {
        $user = $this->makeUser('invoice-title-user');
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/user/invoice-titles', [
            'type' => InvoiceTitleType::Enterprise->value,
            'name' => 'Example Company',
            'tax_no' => 'ABCDEF123456789',
            'is_default' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('name', 'Example Company')
            ->assertJsonPath('tax_id', 'ABCDEF123456789');

        $this->assertDatabaseHas('invoice_titles', [
            'user_id' => $user->id,
            'title' => 'Example Company',
            'tax_no' => 'ABCDEF123456789',
            'is_default' => true,
        ]);
    }

    private function makeUser(string $username, string $password = 'secret-password'): User
    {
        return User::create([
            'username' => $username,
            'password' => $password,
        ]);
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private function makeRegions(): array
    {
        DB::table('regions')->insert([
            [
                'id' => 110000,
                'parent_id' => 0,
                'name' => 'Test Province',
                'pinyin' => 'test-province',
                'level' => RegionLevel::Province->value,
                'sort' => 0,
            ],
            [
                'id' => 110100,
                'parent_id' => 110000,
                'name' => 'Test City',
                'pinyin' => 'test-city',
                'level' => RegionLevel::City->value,
                'sort' => 0,
            ],
            [
                'id' => 110101,
                'parent_id' => 110100,
                'name' => 'Test District',
                'pinyin' => 'test-district',
                'level' => RegionLevel::District->value,
                'sort' => 0,
            ],
        ]);

        return [110000, 110100, 110101];
    }

    private function makeNotification(User $user, bool $read): string
    {
        $id = (string) Str::uuid();

        DB::table('notifications')->insert([
            'id' => $id,
            'type' => DemoNotification::class,
            'notifiable_type' => $user->getMorphClass(),
            'notifiable_id' => $user->id,
            'data' => json_encode([
                'title' => 'Test notification',
                'body' => 'Notification body',
                'color' => 'info',
                'icon' => 'heroicon-o-bell',
                'iconColor' => 'info',
                'status' => 'info',
            ], JSON_THROW_ON_ERROR),
            'read_at' => $read ? now() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }
}
