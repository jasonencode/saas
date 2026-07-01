<?php

namespace Tests\Feature\Campaign;

use App\Enums\Campaign\ExpiredType;
use App\Models\Campaign\Coupon;
use App\Models\User\User;
use App\Services\Campaign\CouponService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class CouponServiceTest extends TestCase
{
    use RefreshDatabase;

    private CouponService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(CouponService::class);
    }

    // ========================================
    // calculateDiscount - 固定金额优惠券
    // ========================================

    public function test_fixed_coupon_returns_discount_amount(): void
    {
        $coupon = Coupon::factory()->fixed()->create(['value' => 10.00]);

        $discount = $this->service->calculateDiscount($coupon, 50.00);

        $this->assertEquals(10.00, $discount);
    }

    public function test_fixed_coupon_discount_not_exceed_total_amount(): void
    {
        $coupon = Coupon::factory()->fixed()->create(['value' => 100.00]);

        $discount = $this->service->calculateDiscount($coupon, 50.00);

        $this->assertEquals(50.00, $discount);
    }

    // ========================================
    // calculateDiscount - 百分比优惠券
    // ========================================

    public function test_percent_coupon_returns_discount_amount(): void
    {
        $coupon = Coupon::factory()->percent()->create([
            'value' => 80,
            'max_discount' => null,
        ]);

        $discount = $this->service->calculateDiscount($coupon, 100.00);

        $this->assertEquals(80.00, $discount);
    }

    public function test_percent_coupon_respects_max_discount(): void
    {
        $coupon = Coupon::factory()->percent()->create([
            'value' => 80,
            'max_discount' => 50.00,
        ]);

        $discount = $this->service->calculateDiscount($coupon, 100.00);

        // 100 * 80% = 80, 但 max_discount = 50
        $this->assertEquals(50.00, $discount);
    }

    public function test_percent_coupon_below_max_discount(): void
    {
        $coupon = Coupon::factory()->percent()->create([
            'value' => 20,
            'max_discount' => 50.00,
        ]);

        $discount = $this->service->calculateDiscount($coupon, 100.00);

        // 100 * 20% = 20, 低于 max_discount
        $this->assertEquals(20.00, $discount);
    }

    // ========================================
    // calculateDiscount - 最低消费
    // ========================================

    public function test_coupon_throws_when_min_amount_not_met(): void
    {
        $coupon = Coupon::factory()->fixed()
            ->withMinAmount(100.00)
            ->create(['value' => 10.00]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/最低需要/');

        $this->service->calculateDiscount($coupon, 50.00);
    }

    public function test_coupon_passes_when_min_amount_met(): void
    {
        $coupon = Coupon::factory()->fixed()
            ->withMinAmount(100.00)
            ->create(['value' => 10.00]);

        $discount = $this->service->calculateDiscount($coupon, 150.00);

        $this->assertEquals(10.00, $discount);
    }

    // ========================================
    // calculateDiscount - 无效/过期优惠券
    // ========================================

    public function test_expired_coupon_throws_exception(): void
    {
        $coupon = Coupon::factory()->expired()->create();

        $this->expectException(InvalidArgumentException::class);

        $this->service->calculateDiscount($coupon, 100.00);
    }

    public function test_disabled_coupon_throws_exception(): void
    {
        $coupon = Coupon::factory()->disabled()->create();

        $this->expectException(InvalidArgumentException::class);

        $this->service->calculateDiscount($coupon, 100.00);
    }

    // ========================================
    // sendToUser - 正常发放
    // ========================================

    public function test_send_coupon_to_user_success(): void
    {
        $coupon = Coupon::factory()->create();
        $user = User::factory()->create();

        $this->service->sendToUser($coupon, $user, 1);

        $this->assertDatabaseHas('coupon_user', [
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_send_multiple_coupons_to_user(): void
    {
        $coupon = Coupon::factory()->create();
        $user = User::factory()->create();

        $this->service->sendToUser($coupon, $user, 3);

        $this->assertEquals(3, $coupon->users()->where('user_id', $user->id)->count());
    }

    public function test_send_coupon_sets_expired_at_for_fixed_type(): void
    {
        $coupon = Coupon::factory()->create([
            'expired_type' => ExpiredType::Fixed,
            'end_at' => now()->addDays(30),
        ]);
        $user = User::factory()->create();

        $this->service->sendToUser($coupon, $user, 1);

        $couponUser = $coupon->users()->where('user_id', $user->id)->first();
        $this->assertNotNull($couponUser->pivot->expired_at);
        $this->assertEquals($coupon->end_at->format('Y-m-d H:i:s'), $couponUser->pivot->expired_at->format('Y-m-d H:i:s'));
    }

    public function test_send_coupon_sets_expired_at_for_receive_type(): void
    {
        $coupon = Coupon::factory()->create([
            'expired_type' => ExpiredType::Receive,
            'days' => 7,
        ]);
        $user = User::factory()->create();

        $this->service->sendToUser($coupon, $user, 1);

        $couponUser = $coupon->users()->where('user_id', $user->id)->first();
        $this->assertNotNull($couponUser->pivot->expired_at);
        $this->assertTrue($couponUser->pivot->expired_at->isAfter(now()->addDays(6)));
    }

    // ========================================
    // sendToUser - 发放上限
    // ========================================

    public function test_send_coupon_fails_when_usage_limit_reached(): void
    {
        $coupon = Coupon::factory()
            ->withUsageLimit(2)
            ->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $this->service->sendToUser($coupon, $user1, 1);
        $this->service->sendToUser($coupon, $user2, 1);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('优惠券发放已达上限');

        $this->service->sendToUser($coupon, $user3, 1);
    }

    public function test_send_coupon_fails_when_qty_exceeds_remaining(): void
    {
        $coupon = Coupon::factory()
            ->withUsageLimit(3)
            ->create();
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/剩余可发放数量不足/');

        $this->service->sendToUser($coupon, $user, 5);
    }

    // ========================================
    // sendToUser - 每人限领
    // ========================================

    public function test_send_coupon_fails_when_per_user_limit_reached(): void
    {
        $coupon = Coupon::factory()
            ->withUsageLimitPerUser(1)
            ->create();
        $user = User::factory()->create();

        $this->service->sendToUser($coupon, $user, 1);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/不可重复领取/');

        $this->service->sendToUser($coupon, $user, 1);
    }

    public function test_send_coupon_fails_when_qty_exceeds_per_user_remaining(): void
    {
        $coupon = Coupon::factory()
            ->withUsageLimitPerUser(3)
            ->create();
        $user = User::factory()->create();

        $this->service->sendToUser($coupon, $user, 1);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/最多还可领取/');

        $this->service->sendToUser($coupon, $user, 3);
    }
}
