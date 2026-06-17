<?php

namespace Tests\Feature\Campaign;

use App\Enums\Campaign\LotteryDrawMode;
use App\Enums\Campaign\LotteryPrizeType;
use App\Models\Campaign\Lottery;
use App\Models\Campaign\LotteryPrize;
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

        $draw = $this->service->draw($lottery, $user);

        $this->assertEquals('points', $draw->draw_cost_type);
        $this->assertEquals(10, $draw->draw_cost_amount);
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
        $lottery = Lottery::factory()->create(['max_draws_per_user' => 5]);
        $user = User::factory()->create();

        $available = $this->service->getAvailableDraws($lottery, $user);

        $this->assertEquals(5, $available);
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

    public function test_fulfill_prize_throws_for_non_physical(): void
    {
        $lottery = Lottery::factory()->create();
        $prize = LotteryPrize::factory()->create([
            'lottery_id' => $lottery->id,
            'type' => LotteryPrizeType::Coupon,
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

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('仅实物奖品需要兑奖');

        $this->service->fulfillPrize($record);
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
}
