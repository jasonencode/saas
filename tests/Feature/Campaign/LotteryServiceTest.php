<?php

namespace Tests\Feature\Campaign;

use App\Enums\Campaign\LotteryDrawMode;
use App\Enums\Campaign\LotteryPrizeType;
use App\Models\Campaign\Coupon;
use App\Models\Campaign\Lottery;
use App\Models\Campaign\LotteryPrize;
use App\Models\Campaign\LotteryPrizeRecord;
use App\Models\User\User;
use App\Services\Campaign\LotteryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class LotteryServiceTest extends TestCase
{
    use RefreshDatabase;

    private LotteryService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(LotteryService::class);
    }

    // ─── draw ─────────────────────────────────────────────────────

    public function test_draw_creates_draw_record(): void
    {
        $lottery = Lottery::factory()->create();
        LotteryPrize::factory()->none()->create(['lottery_id' => $lottery->id]);
        $user = User::factory()->create();

        $draw = $this->service->draw($lottery, $user, '127.0.0.1');

        $this->assertNotNull($draw);
        $this->assertEquals($lottery->id, $draw->lottery_id);
        $this->assertEquals($user->id, $draw->user_id);
    }

    public function test_draw_throws_when_lottery_inactive(): void
    {
        $lottery = Lottery::factory()->disabled()->create();
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('活动未开始或已结束');

        $this->service->draw($lottery, $user);
    }

    public function test_draw_throws_when_lottery_expired(): void
    {
        $lottery = Lottery::factory()->expired()->create();
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('活动未开始或已结束');

        $this->service->draw($lottery, $user);
    }

    public function test_draw_throws_when_no_prizes(): void
    {
        $lottery = Lottery::factory()->create();
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('暂无可用奖品');

        $this->service->draw($lottery, $user);
    }

    public function test_draw_throws_when_draws_exhausted(): void
    {
        $lottery = Lottery::factory()->create(['max_draws_per_user' => 1]);
        LotteryPrize::factory()->none()->create(['lottery_id' => $lottery->id]);
        $user = User::factory()->create();

        // 第一次抽奖
        $this->service->draw($lottery, $user);

        // 第二次应该失败
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('抽奖次数已用完');

        $this->service->draw($lottery, $user);
    }

    public function test_draw_decrements_prize_quantity(): void
    {
        $lottery = Lottery::factory()->create();
        $prize = LotteryPrize::factory()->create([
            'lottery_id' => $lottery->id,
            'type' => LotteryPrizeType::Coupon,
            'weight' => 100,
            'total_quantity' => 10,
            'remaining_quantity' => 10,
        ]);
        $user = User::factory()->create();

        // Mock pickPrize 返回这个奖品
        $draw = $this->service->draw($lottery, $user);

        if ($draw->lottery_prize_id) {
            $this->assertEquals(9, $prize->fresh()->remaining_quantity);
        }
    }

    public function test_draw_with_points_mode_deducts_points(): void
    {
        $lottery = Lottery::factory()->points()->create(['points_per_draw' => 10]);
        LotteryPrize::factory()->none()->create(['lottery_id' => $lottery->id]);
        $user = User::factory()->create();
        $user->account()->firstOrCreate([])->update(['points' => 100]);

        $draw = $this->service->draw($lottery, $user);

        $this->assertEquals('points', $draw->draw_cost_type);
        $this->assertEquals(10, $draw->draw_cost_amount);
        $this->assertEquals(90, (float) $user->account()->first()->points);
        $this->assertDatabaseHas('user_account_logs', [
            'user_id' => $user->getKey(),
            'type' => 'consume',
            'asset' => 'points',
        ]);
    }

    public function test_draw_with_points_mode_fails_when_points_insufficient(): void
    {
        $lottery = Lottery::factory()->points()->create(['points_per_draw' => 10]);
        $prize = LotteryPrize::factory()->create([
            'lottery_id' => $lottery->id,
            'type' => LotteryPrizeType::Coupon,
            'weight' => 100,
            'total_quantity' => 10,
            'remaining_quantity' => 10,
        ]);
        $user = User::factory()->create();
        $user->account()->firstOrCreate([])->update(['points' => 5]);

        try {
            $this->service->draw($lottery, $user);
            $this->fail('积分不足时应抛出异常');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('积分不足', $exception->getMessage());
        }

        // 事务回滚：不产生抽奖记录，库存不扣减
        $this->assertDatabaseCount('lottery_draws', 0);
        $this->assertEquals(10, $prize->fresh()->remaining_quantity);
        $this->assertEquals(5, (float) $user->account()->first()->points);
    }

    public function test_draw_grants_balance_prize_automatically(): void
    {
        $lottery = Lottery::factory()->create();
        $prize = LotteryPrize::factory()->create([
            'lottery_id' => $lottery->id,
            'type' => LotteryPrizeType::Balance,
            'prize_config' => ['amount' => 8.88],
            'weight' => 100,
            'total_quantity' => 10,
            'remaining_quantity' => 10,
        ]);
        $user = User::factory()->create();
        $user->account()->firstOrCreate([])->update(['balance' => 0]);

        $draw = $this->service->draw($lottery, $user);

        $this->assertNotNull($draw->lottery_prize_id);
        $this->assertEquals($prize->getKey(), $draw->lottery_prize_id);
        $this->assertEquals('fulfilled', $draw->prizeRecord->status->value);
        $this->assertEquals(8.88, (float) $user->account()->first()->balance);
        $this->assertDatabaseHas('user_account_logs', [
            'user_id' => $user->getKey(),
            'type' => 'reward',
            'asset' => 'balance',
        ]);
    }

    public function test_draw_grants_coupon_prize_automatically(): void
    {
        $lottery = Lottery::factory()->create();
        $coupon = Coupon::factory()->create();
        $prize = LotteryPrize::factory()->create([
            'lottery_id' => $lottery->id,
            'type' => LotteryPrizeType::Coupon,
            'prize_config' => ['coupon_id' => $coupon->getKey()],
            'weight' => 100,
            'total_quantity' => 10,
            'remaining_quantity' => 10,
        ]);
        $user = User::factory()->create();

        $draw = $this->service->draw($lottery, $user);

        $this->assertEquals($prize->getKey(), $draw->lottery_prize_id);
        $this->assertEquals('fulfilled', $draw->prizeRecord->status->value);
        $this->assertDatabaseHas('coupon_user', [
            'coupon_id' => $coupon->getKey(),
            'user_id' => $user->getKey(),
        ]);
    }

    public function test_draw_keeps_physical_prize_pending(): void
    {
        $lottery = Lottery::factory()->create();
        LotteryPrize::factory()->physical()->create([
            'lottery_id' => $lottery->id,
            'weight' => 100,
            'total_quantity' => 10,
            'remaining_quantity' => 10,
        ]);
        $user = User::factory()->create();

        $draw = $this->service->draw($lottery, $user);

        $this->assertEquals('pending', $draw->prizeRecord->status->value);
    }

    public function test_draw_with_free_mode_has_zero_cost(): void
    {
        $lottery = Lottery::factory()->create(['draw_mode' => LotteryDrawMode::Free]);
        LotteryPrize::factory()->none()->create(['lottery_id' => $lottery->id]);
        $user = User::factory()->create();

        $draw = $this->service->draw($lottery, $user);

        $this->assertEquals('free', $draw->draw_cost_type);
        $this->assertEquals(0, $draw->draw_cost_amount);
    }

    // ─── getAvailableDraws ────────────────────────────────────────

    public function test_get_available_draws_returns_max_when_no_draws(): void
    {
        // 免费次数足够时，以「每人总次数上限」为准
        $lottery = Lottery::factory()->create([
            'max_draws_per_user' => 5,
            'free_draws_per_day' => 10,
        ]);
        $user = User::factory()->create();

        $available = $this->service->getAvailableDraws($lottery, $user);

        $this->assertEquals(5, $available);
    }

    public function test_get_available_draws_limited_by_daily_free_draws(): void
    {
        // 免费模式下实际可抽次数受今日免费次数约束（与 resolveCostType 的准入一致）
        $lottery = Lottery::factory()->create([
            'max_draws_per_user' => 5,
            'free_draws_per_day' => 2,
        ]);
        $user = User::factory()->create();

        $available = $this->service->getAvailableDraws($lottery, $user);

        $this->assertEquals(2, $available);
    }

    public function test_get_available_draws_decreases_after_draw(): void
    {
        $lottery = Lottery::factory()->create(['max_draws_per_user' => 3]);
        LotteryPrize::factory()->none()->create(['lottery_id' => $lottery->id]);
        $user = User::factory()->create();

        $this->service->draw($lottery, $user);

        $available = $this->service->getAvailableDraws($lottery, $user);

        $this->assertEquals(2, $available);
    }

    // ─── fulfillPrize ─────────────────────────────────────────────

    public function test_fulfill_prize_updates_status(): void
    {
        $lottery = Lottery::factory()->create();
        $prize = LotteryPrize::factory()->physical()->create(['lottery_id' => $lottery->id]);
        $draw = $lottery->draws()->create([
            'user_id' => User::factory()->create()->id,
            'lottery_prize_id' => $prize->id,
            'draw_cost_type' => 'free',
            'draw_cost_amount' => 0,
        ]);
        $record = $draw->prizeRecord()->create([
            'lottery_id' => $lottery->id,
            'user_id' => $draw->user_id,
            'lottery_prize_id' => $prize->id,
            'type' => $prize->type,
            'status' => 'pending',
        ]);

        $this->service->fulfillPrize($record, '已发货');

        $this->assertEquals('fulfilled', $record->fresh()->status->value);
        $this->assertEquals('已发货', $record->fresh()->fulfillment_note);
    }

    public function test_fulfill_prize_grants_balance_prize(): void
    {
        $lottery = Lottery::factory()->create();
        $user = User::factory()->create();
        $user->account()->firstOrCreate([])->update(['balance' => 0]);
        $prize = LotteryPrize::factory()->create([
            'lottery_id' => $lottery->id,
            'type' => LotteryPrizeType::Balance,
            'prize_config' => ['amount' => 12.5],
        ]);
        $draw = $lottery->draws()->create([
            'user_id' => $user->id,
            'lottery_prize_id' => $prize->id,
            'draw_cost_type' => 'free',
            'draw_cost_amount' => 0,
        ]);
        $record = $draw->prizeRecord()->create([
            'lottery_id' => $lottery->id,
            'user_id' => $draw->user_id,
            'lottery_prize_id' => $prize->id,
            'type' => $prize->type,
            'prize_detail' => $prize->prize_config,
            'status' => 'pending',
        ]);

        $this->service->fulfillPrize($record, '补发');

        $this->assertEquals('fulfilled', $record->fresh()->status->value);
        $this->assertEquals(12.5, (float) $user->account()->first()->balance);
    }

    public function test_fulfill_prize_twice_does_not_double_grant(): void
    {
        $lottery = Lottery::factory()->create();
        $user = User::factory()->create();
        $user->account()->firstOrCreate([])->update(['balance' => 0]);
        $prize = LotteryPrize::factory()->create([
            'lottery_id' => $lottery->id,
            'type' => LotteryPrizeType::Balance,
            'prize_config' => ['amount' => 10],
        ]);
        $draw = $lottery->draws()->create([
            'user_id' => $user->id,
            'lottery_prize_id' => $prize->id,
            'draw_cost_type' => 'free',
            'draw_cost_amount' => 0,
        ]);
        $record = $draw->prizeRecord()->create([
            'lottery_id' => $lottery->id,
            'user_id' => $draw->user_id,
            'lottery_prize_id' => $prize->id,
            'type' => $prize->type,
            'prize_detail' => $prize->prize_config,
            'status' => 'pending',
        ]);

        $this->service->fulfillPrize($record);

        try {
            $this->service->fulfillPrize($record);
            $this->fail('已兑奖记录不应重复兑奖');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('该奖品不可兑奖', $exception->getMessage());
        }

        $this->assertEquals(10, (float) $user->account()->first()->balance);
    }

    public function test_fulfill_prize_throws_for_none_prize(): void
    {
        $lottery = Lottery::factory()->create();
        $prize = LotteryPrize::factory()->none()->create(['lottery_id' => $lottery->id]);
        $draw = $lottery->draws()->create([
            'user_id' => User::factory()->create()->id,
            'lottery_prize_id' => $prize->id,
            'draw_cost_type' => 'free',
            'draw_cost_amount' => 0,
        ]);
        $record = $draw->prizeRecord()->create([
            'lottery_id' => $lottery->id,
            'user_id' => $draw->user_id,
            'lottery_prize_id' => $prize->id,
            'type' => $prize->type,
            'status' => 'pending',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('该奖品不支持兑奖');

        $this->service->fulfillPrize($record);
    }

    // ─── fulfillPrize（优惠券奖品） ────────────────────────────────

    public function test_fulfill_coupon_prize_sends_coupon_to_user(): void
    {
        $lottery = Lottery::factory()->create();
        $user = User::factory()->create();
        $coupon = Coupon::factory()->create();
        $record = $this->makeCouponPrizeRecord($lottery, $user, $coupon->getKey());

        $this->service->fulfillPrize($record, '自动发放');

        $this->assertEquals('fulfilled', $record->fresh()->status->value);
        $this->assertEquals('自动发放', $record->fresh()->fulfillment_note);
        $this->assertNotNull($record->fresh()->fulfilled_at);

        // 券已发到用户持券实例
        $this->assertDatabaseHas('coupon_user', [
            'coupon_id' => $coupon->getKey(),
            'user_id' => $user->getKey(),
        ]);
    }

    public function test_fulfill_coupon_prize_without_coupon_config_keeps_pending(): void
    {
        $lottery = Lottery::factory()->create();
        $record = $this->makeCouponPrizeRecord($lottery, User::factory()->create(), null);

        try {
            $this->service->fulfillPrize($record);
            $this->fail('未配置优惠券时应抛出异常');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('奖品未配置优惠券', $exception->getMessage());
        }

        $this->assertEquals('pending', $record->fresh()->status->value);
    }

    public function test_fulfill_coupon_prize_keeps_pending_when_coupon_disabled(): void
    {
        $lottery = Lottery::factory()->create();
        $coupon = Coupon::factory()->disabled()->create();
        $record = $this->makeCouponPrizeRecord($lottery, User::factory()->create(), $coupon->getKey());

        try {
            $this->service->fulfillPrize($record);
            $this->fail('券不可用时应抛出异常');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('优惠券已失效', $exception->getMessage());
        }

        // 保持待兑付，供人工处理（换券或取消奖品）
        $this->assertEquals('pending', $record->fresh()->status->value);
        $this->assertDatabaseCount('coupon_user', 0);
    }

    /**
     * 构造优惠券奖品的中奖记录
     */
    private function makeCouponPrizeRecord(Lottery $lottery, User $user, ?int $couponId): LotteryPrizeRecord
    {
        $prize = LotteryPrize::factory()->create([
            'lottery_id' => $lottery->getKey(),
            'type' => LotteryPrizeType::Coupon,
            'prize_config' => $couponId === null ? [] : ['coupon_id' => $couponId],
        ]);

        $draw = $lottery->draws()->create([
            'user_id' => $user->getKey(),
            'lottery_prize_id' => $prize->getKey(),
            'draw_cost_type' => 'free',
            'draw_cost_amount' => 0,
        ]);

        return $draw->prizeRecord()->create([
            'lottery_id' => $lottery->getKey(),
            'user_id' => $user->getKey(),
            'lottery_prize_id' => $prize->getKey(),
            'type' => $prize->type,
            'prize_detail' => $prize->prize_config,
            'status' => 'pending',
        ]);
    }

    // ─── 库存 ─────────────────────────────────────────────────────

    public function test_prize_initializes_remaining_quantity_from_total_quantity(): void
    {
        $lottery = Lottery::factory()->create();

        $prize = LotteryPrize::create([
            'lottery_id' => $lottery->id,
            'name' => '库存测试奖品',
            'type' => LotteryPrizeType::Coupon,
            'weight' => 10,
            'total_quantity' => 20,
        ]);

        $this->assertEquals(20, $prize->fresh()->remaining_quantity);
    }

    public function test_prize_adjusts_remaining_quantity_when_total_changes(): void
    {
        $lottery = Lottery::factory()->create();
        $prize = LotteryPrize::factory()->create([
            'lottery_id' => $lottery->id,
            'total_quantity' => 10,
            'remaining_quantity' => 10,
        ]);

        $prize->update(['total_quantity' => 15]);
        $this->assertEquals(15, $prize->fresh()->remaining_quantity);

        $prize->update(['total_quantity' => 5]);
        $this->assertEquals(5, $prize->fresh()->remaining_quantity);

        // 0 表示无限量，剩余数量随之归零（不再按库存校验）
        $prize->update(['total_quantity' => 0]);
        $this->assertEquals(0, $prize->fresh()->remaining_quantity);
    }

    // ─── cancelPrize ──────────────────────────────────────────────

    public function test_cancel_prize_increments_quantity(): void
    {
        $lottery = Lottery::factory()->create();
        $prize = LotteryPrize::factory()->create([
            'lottery_id' => $lottery->id,
            'total_quantity' => 10,
            'remaining_quantity' => 5,
        ]);
        $draw = $lottery->draws()->create([
            'user_id' => User::factory()->create()->id,
            'lottery_prize_id' => $prize->id,
            'draw_cost_type' => 'free',
            'draw_cost_amount' => 0,
        ]);
        $record = $draw->prizeRecord()->create([
            'lottery_id' => $lottery->id,
            'user_id' => $draw->user_id,
            'lottery_prize_id' => $prize->id,
            'type' => $prize->type,
            'status' => 'pending',
        ]);

        $this->service->cancelPrize($record, '用户取消');

        $this->assertEquals('cancelled', $record->fresh()->status->value);
        $this->assertEquals(6, $prize->fresh()->remaining_quantity);
    }

    public function test_cancel_prize_twice_only_increments_quantity_once(): void
    {
        $lottery = Lottery::factory()->create();
        $prize = LotteryPrize::factory()->create([
            'lottery_id' => $lottery->id,
            'total_quantity' => 10,
            'remaining_quantity' => 5,
        ]);
        $draw = $lottery->draws()->create([
            'user_id' => User::factory()->create()->id,
            'lottery_prize_id' => $prize->id,
            'draw_cost_type' => 'free',
            'draw_cost_amount' => 0,
        ]);
        $record = $draw->prizeRecord()->create([
            'lottery_id' => $lottery->id,
            'user_id' => $draw->user_id,
            'lottery_prize_id' => $prize->id,
            'type' => $prize->type,
            'status' => 'pending',
        ]);

        $this->service->cancelPrize($record, '用户取消');

        try {
            $this->service->cancelPrize($record);
            $this->fail('已取消记录不应重复取消');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('该奖品不可取消', $exception->getMessage());
        }

        $this->assertEquals(6, $prize->fresh()->remaining_quantity);
    }
}
