<?php

namespace Tests\Feature\Finance;

use App\Enums\Finance\AccountAssetType;
use App\Models\Finance\UserAccount;
use App\Models\User\User;
use App\Services\Finance\UserAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class UserAccountServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserAccountService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(UserAccountService::class);
    }

    // ─── modifyAsset ──────────────────────────────────────────────

    public function test_modify_asset_increases_balance(): void
    {
        $account = $this->createAccount(balance: 100);

        $result = $this->service->modifyAsset(
            account: $account,
            asset: AccountAssetType::Balance,
            amount: 50,
            remark: '测试充值'
        );

        $this->assertTrue($result);
        $this->assertEquals(150, $account->fresh()->balance);
    }

    public function test_modify_asset_decreases_balance(): void
    {
        $account = $this->createAccount(balance: 100);

        $result = $this->service->modifyAsset(
            account: $account,
            asset: AccountAssetType::Balance,
            amount: -30,
            remark: '测试扣款'
        );

        $this->assertTrue($result);
        $this->assertEquals(70, $account->fresh()->balance);
    }

    public function test_modify_asset_throws_when_insufficient_balance(): void
    {
        $account = $this->createAccount(balance: 100);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('余额不足');

        $this->service->modifyAsset(
            account: $account,
            asset: AccountAssetType::Balance,
            amount: -150,
            remark: '超额扣款'
        );
    }

    public function test_modify_asset_increases_points(): void
    {
        $account = $this->createAccount(points: 500);

        $result = $this->service->modifyAsset(
            account: $account,
            asset: AccountAssetType::Points,
            amount: 200,
            remark: '奖励积分'
        );

        $this->assertTrue($result);
        $this->assertEquals(700, $account->fresh()->points);
    }

    public function test_modify_asset_creates_log(): void
    {
        $account = $this->createAccount(balance: 100);

        $this->service->modifyAsset(
            account: $account,
            asset: AccountAssetType::Balance,
            amount: 50,
            remark: '测试充值'
        );

        $this->assertDatabaseHas('user_account_logs', [
            'user_id' => $account->user_id,
            'asset' => AccountAssetType::Balance->value,
            'amount' => 50,
            'before' => 100,
            'after' => 150,
            'remark' => '测试充值',
        ]);
    }

    // ─── frozenAsset ──────────────────────────────────────────────

    public function test_freeze_asset_moves_balance_to_frozen(): void
    {
        $account = $this->createAccount(balance: 100);

        $result = $this->service->frozenAsset(
            account: $account,
            asset: AccountAssetType::Balance,
            amount: 40,
            isFreeze: true,
            remark: '冻结测试'
        );

        $this->assertTrue($result);

        $account->refresh();
        $this->assertEquals(60, $account->balance);
        $this->assertEquals(40, $account->frozen_balance);
    }

    public function test_unfreeze_asset_moves_frozen_to_balance(): void
    {
        $account = $this->createAccount(balance: 60, frozen_balance: 40);

        $result = $this->service->frozenAsset(
            account: $account,
            asset: AccountAssetType::Balance,
            amount: 20,
            isFreeze: false,
            remark: '解冻测试'
        );

        $this->assertTrue($result);

        $account->refresh();
        $this->assertEquals(80, $account->balance);
        $this->assertEquals(20, $account->frozen_balance);
    }

    public function test_freeze_throws_when_insufficient_balance(): void
    {
        $account = $this->createAccount(balance: 30);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('可用余额不足');

        $this->service->frozenAsset(
            account: $account,
            asset: AccountAssetType::Balance,
            amount: 50,
            isFreeze: true,
            remark: '超额冻结'
        );
    }

    public function test_unfreeze_throws_when_insufficient_frozen(): void
    {
        $account = $this->createAccount(balance: 60, frozen_balance: 20);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('冻结余额不足');

        $this->service->frozenAsset(
            account: $account,
            asset: AccountAssetType::Balance,
            amount: 30,
            isFreeze: false,
            remark: '超额解冻'
        );
    }

    public function test_freeze_creates_log_with_frozen_info(): void
    {
        $account = $this->createAccount(balance: 100);

        $this->service->frozenAsset(
            account: $account,
            asset: AccountAssetType::Balance,
            amount: 40,
            isFreeze: true,
            remark: '冻结测试'
        );

        $this->assertDatabaseHas('user_account_logs', [
            'user_id' => $account->user_id,
            'asset' => AccountAssetType::Balance->value,
            'amount' => -40,
            'before' => 100,
            'after' => 60,
            'remark' => '冻结测试',
        ]);
    }

    // ─── Helper ───────────────────────────────────────────────────

    private function createAccount(
        float $balance = 0,
        float $frozen_balance = 0,
        float $points = 0,
        float $frozen_points = 0
    ): UserAccount {
        $user = User::factory()->create();

        // User 创建时会自动创建 UserAccount，直接使用
        $account = $user->account;
        $account->update([
            'balance' => $balance,
            'frozen_balance' => $frozen_balance,
            'points' => $points,
            'frozen_points' => $frozen_points,
        ]);

        return $account->fresh();
    }
}
