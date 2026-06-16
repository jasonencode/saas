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
