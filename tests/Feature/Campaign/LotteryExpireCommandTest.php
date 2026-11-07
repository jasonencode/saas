<?php

namespace Tests\Feature\Campaign;

use App\Enums\Campaign\LotteryPrizeStatus;
use App\Models\Campaign\Lottery;
use App\Models\Campaign\LotteryPrize;
use App\Models\Campaign\LotteryPrizeRecord;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LotteryExpireCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_marks_pending_records_of_ended_lotteries_as_expired(): void
    {
        $expiredLottery = Lottery::factory()->expired()->create();
        $activeLottery = Lottery::factory()->create();

        $expiredRecord = $this->makePendingRecord($expiredLottery);
        $activeRecord = $this->makePendingRecord($activeLottery);

        $this->artisan('lottery:expire')->assertSuccessful();

        $this->assertEquals(LotteryPrizeStatus::Expired, $expiredRecord->fresh()->status);
        $this->assertEquals(LotteryPrizeStatus::Pending, $activeRecord->fresh()->status);
    }

    public function test_does_not_change_already_fulfilled_records(): void
    {
        $lottery = Lottery::factory()->expired()->create();
        $record = $this->makePendingRecord($lottery, LotteryPrizeStatus::Fulfilled);

        $this->artisan('lottery:expire')->assertSuccessful();

        $this->assertEquals(LotteryPrizeStatus::Fulfilled, $record->fresh()->status);
    }

    /**
     * 构造指定状态的奖品记录
     */
    private function makePendingRecord(Lottery $lottery, LotteryPrizeStatus $status = LotteryPrizeStatus::Pending): LotteryPrizeRecord
    {
        $user = User::factory()->create();
        $prize = LotteryPrize::factory()->physical()->create(['lottery_id' => $lottery->getKey()]);
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
            'status' => $status,
        ]);
    }
}
