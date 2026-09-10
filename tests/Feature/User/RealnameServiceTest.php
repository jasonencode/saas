<?php

namespace Tests\Feature\User;

use App\Enums\User\RealnameStatus;
use App\Enums\User\RealnameType;
use App\Events\User\UserRealnameApproved;
use App\Events\User\UserRealnameRejected;
use App\Models\User\User;
use App\Models\User\UserRealname;
use App\Services\User\RealnameService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Tests\TestCase;

class RealnameServiceTest extends TestCase
{
    use RefreshDatabase;

    private RealnameService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RealnameService::class);
    }

    public function test_approve_marks_realname_as_approved_and_dispatches_event(): void
    {
        Event::fake();
        $realname = $this->makePendingRealname();

        $this->service->approve($realname);

        $realname->refresh();

        $this->assertSame(RealnameStatus::Approved, $realname->status);
        $this->assertNotNull($realname->verified_at);

        Event::assertDispatched(UserRealnameApproved::class, function (UserRealnameApproved $event) use ($realname): bool {
            return $event->realname->is($realname);
        });
    }

    public function test_reject_marks_realname_as_rejected_and_dispatches_event(): void
    {
        Event::fake();
        $realname = $this->makePendingRealname();

        $this->service->reject($realname, '证件信息不清晰');

        $realname->refresh();

        $this->assertSame(RealnameStatus::Rejected, $realname->status);
        $this->assertSame('证件信息不清晰', $realname->reject_reason);

        Event::assertDispatched(UserRealnameRejected::class, function (UserRealnameRejected $event) use ($realname): bool {
            return $event->realname->is($realname)
                && $event->reason === '证件信息不清晰';
        });
    }

    private function submitData(): array
    {
        return [
            'name' => '张三',
            'id_card_number' => '110101199001011234',
            'id_card_front' => '2026/09/09/front.jpg',
            'id_card_back' => '2026/09/09/back.jpg',
        ];
    }

    public function test_submit_creates_pending_realname_when_none_exists(): void
    {
        $user = User::factory()->create();

        $realname = $this->service->submit($user->id, RealnameType::Personal, $this->submitData());

        $this->assertDatabaseHas('user_realnames', [
            'user_id' => $user->id,
            'type' => RealnameType::Personal->value,
            'status' => RealnameStatus::Pending->value,
            'name' => '张三',
        ]);
        $this->assertSame(RealnameStatus::Pending, $realname->status);
        $this->assertNull($realname->verified_at);
    }

    public function test_submit_throws_when_already_pending(): void
    {
        $user = User::factory()->create();
        $this->service->submit($user->id, RealnameType::Personal, $this->submitData());

        $this->expectException(InvalidArgumentException::class);

        $this->service->submit($user->id, RealnameType::Personal, $this->submitData());
    }

    public function test_submit_throws_when_already_approved(): void
    {
        $user = User::factory()->create();
        $realname = $this->service->submit($user->id, RealnameType::Personal, $this->submitData());
        $this->service->approve($realname);

        $this->expectException(InvalidArgumentException::class);

        $this->service->submit($user->id, RealnameType::Personal, $this->submitData());
    }

    public function test_submit_resets_pending_when_rejected(): void
    {
        $user = User::factory()->create();
        $realname = $this->service->submit($user->id, RealnameType::Personal, $this->submitData());
        $this->service->reject($realname, '证件不清晰');
        $this->assertSame(RealnameStatus::Rejected, $realname->refresh()->status);

        // 更新同一认证类型的原记录，不新增
        $updated = $this->service->submit($user->id, RealnameType::Personal, $this->submitData());

        $this->assertTrue($updated->is($realname));
        $this->assertSame(RealnameStatus::Pending, $updated->status);
        $this->assertNull($updated->reject_reason);
        $this->assertNull($updated->verified_at);
        $this->assertDatabaseCount('user_realnames', 1);
    }

    private function makePendingRealname(): UserRealname
    {
        $user = User::factory()->create();

        return UserRealname::create([
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'type' => RealnameType::Personal,
            'status' => RealnameStatus::Pending,
            'name' => '测试用户',
            'id_card_number' => '110101199001011234',
            'contact_phone' => '13800138000',
        ]);
    }
}
