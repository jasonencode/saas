<?php

namespace Tests\Feature\Campaign;

use App\Enums\Campaign\RedpackCodeStatus;
use App\Jobs\Campaign\SendRedpackJob;
use App\Models\Campaign\Redpack;
use App\Models\Campaign\RedpackCode;
use App\Models\Foundation\WechatPayment;
use App\Models\System\Tenant;
use App\Models\User\User;
use App\Services\Campaign\RedpackService;
use App\Services\Foundation\WechatPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Yansongda\Supports\Collection;

class RedpackClaimTest extends TestCase
{
    use RefreshDatabase;

    public function test_claim_marks_code_as_claimed_and_dispatches_job(): void
    {
        Queue::fake();

        [$redpack, $user] = $this->fixture();
        $code = $redpack->codes()->create([
            'amount' => 10,
            'status' => RedpackCodeStatus::Active,
        ]);

        $claimed = service(RedpackService::class)->claim($code, $user, '127.0.0.1');

        $this->assertSame(RedpackCodeStatus::Claimed, $claimed->status);
        $this->assertSame($user->getKey(), $claimed->user_id);
        $this->assertSame('127.0.0.1', $claimed->claimed_ip);
        $this->assertNotNull($claimed->claimed_at);

        Queue::assertPushed(SendRedpackJob::class);
    }

    public function test_second_claim_is_rejected(): void
    {
        Queue::fake();

        [$redpack, $user] = $this->fixture();
        $code = $redpack->codes()->create([
            'amount' => 10,
            'status' => RedpackCodeStatus::Active,
        ]);

        service(RedpackService::class)->claim($code, $user);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('红包码无效或已被领取');

        service(RedpackService::class)->claim($code->fresh(), User::factory()->create());
    }

    public function test_api_claim_succeeds_for_tenant_member(): void
    {
        Queue::fake();

        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $user->tenants()->attach($tenant);
        $redpack = Redpack::factory()->create(['tenant_id' => $tenant->getKey()]);
        $code = $redpack->codes()->create([
            'amount' => 12.5,
            'status' => RedpackCodeStatus::Active,
        ]);

        Sanctum::actingAs($user);

        $this->withHeader('X-Tenant-Id', (string) $tenant->getKey())
            ->postJson("/api/campaign/redpacks/{$code->code}/claim")
            ->assertOk()
            ->assertJsonPath('amount', '12.50')
            ->assertJsonPath('claimed_at', $code->fresh()->claimed_at->toDateTimeString());

        $this->assertDatabaseHas('event_logs', [
            'event' => 'redpack.claimed',
            'subject_type' => RedpackCode::class,
            'subject_id' => $code->getKey(),
        ]);
    }

    public function test_claim_limit_per_user_is_enforced(): void
    {
        Queue::fake();

        [$redpack, $user] = $this->fixture();
        $redpack->update(['limit_per_user' => 1]);

        $first = $redpack->codes()->create([
            'amount' => 10,
            'status' => RedpackCodeStatus::Active,
        ]);
        $second = $redpack->codes()->create([
            'amount' => 10,
            'status' => RedpackCodeStatus::Active,
        ]);

        service(RedpackService::class)->claim($first, $user);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('已达该活动的领取上限');

        service(RedpackService::class)->claim($second, $user);
    }

    public function test_claim_rate_limit(): void
    {
        Queue::fake();
        config(['custom.rate_limits.redpack_claim' => 2]);

        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $user->tenants()->attach($tenant);
        $redpack = Redpack::factory()->create(['tenant_id' => $tenant->getKey()]);

        $codes = collect(range(1, 3))->map(fn (): RedpackCode => $redpack->codes()->create([
            'amount' => 10,
            'status' => RedpackCodeStatus::Active,
        ]));

        Sanctum::actingAs($user);

        foreach ($codes->take(2) as $code) {
            $this->withHeader('X-Tenant-Id', (string) $tenant->getKey())
                ->postJson("/api/campaign/redpacks/{$code->code}/claim")
                ->assertOk();
        }

        $this->withHeader('X-Tenant-Id', (string) $tenant->getKey())
            ->postJson("/api/campaign/redpacks/{$codes->last()->code}/claim")
            ->assertStatus(429);
    }

    public function test_expire_command_disables_codes_of_ended_redpack(): void
    {
        $ended = Redpack::factory()->create(['end_at' => now()->subHour()]);
        $active = Redpack::factory()->create(['end_at' => now()->addHour()]);

        $endedCode = $ended->codes()->create(['amount' => 10, 'status' => RedpackCodeStatus::Active]);
        $activeCode = $active->codes()->create(['amount' => 10, 'status' => RedpackCodeStatus::Active]);

        $this->artisan('redpack:expire')->assertSuccessful();

        $this->assertSame(RedpackCodeStatus::Disabled, $endedCode->fresh()->status);
        $this->assertSame(RedpackCodeStatus::Active, $activeCode->fresh()->status);
    }

    public function test_api_claim_is_scoped_to_tenant(): void
    {
        Queue::fake();

        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $user->tenants()->attach($tenant);
        $redpack = Redpack::factory()->create(['tenant_id' => $otherTenant->getKey()]);
        $code = $redpack->codes()->create([
            'amount' => 10,
            'status' => RedpackCodeStatus::Active,
        ]);

        Sanctum::actingAs($user);

        $this->withHeader('X-Tenant-Id', (string) $tenant->getKey())
            ->postJson("/api/campaign/redpacks/{$code->code}/claim")
            ->assertNotFound();

        $this->assertSame(RedpackCodeStatus::Active, $code->fresh()->status);
    }

    public function test_api_claim_rejected_without_tenant_membership(): void
    {
        Queue::fake();

        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $redpack = Redpack::factory()->create(['tenant_id' => $tenant->getKey()]);
        $code = $redpack->codes()->create([
            'amount' => 10,
            'status' => RedpackCodeStatus::Active,
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/campaign/redpacks/{$code->code}/claim")
            ->assertNotFound();

        $this->assertSame(RedpackCodeStatus::Active, $code->fresh()->status);
    }

    public function test_redpack_list_is_scoped_by_tenant_header(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();

        Redpack::factory()->create(['tenant_id' => $tenant->getKey(), 'name' => 'A红包']);
        Redpack::factory()->create(['tenant_id' => $otherTenant->getKey(), 'name' => 'B红包']);

        $this->withHeader('X-Tenant-Id', (string) $tenant->getKey())
            ->getJson('/api/campaign/redpacks')
            ->assertOk()
            ->assertJsonPath('0.name', 'A红包')
            ->assertJsonCount(1);
    }

    public function test_redpack_detail_hides_other_tenants(): void
    {
        $tenant = Tenant::factory()->create();
        $redpack = Redpack::factory()->create([
            'tenant_id' => Tenant::factory()->create()->getKey(),
            'name' => '别人的红包',
        ]);

        $this->withHeader('X-Tenant-Id', (string) $tenant->getKey())
            ->getJson("/api/campaign/redpacks/{$redpack->getKey()}")
            ->assertNotFound();
    }

    public function test_redpack_job_reuses_bill_no_and_skips_sent_codes(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $user->tenants()->attach($tenant);
        $redpack = Redpack::factory()->create(['tenant_id' => $tenant->getKey()]);

        $code = $redpack->codes()->create([
            'amount' => 10,
            'status' => RedpackCodeStatus::Claimed,
            'user_id' => $user->getKey(),
            'bill_no' => 'RP-EXISTING',
        ]);

        $payment = WechatPayment::create([
            'tenant_id' => $tenant->getKey(),
            'wechat_id' => 1,
            'name' => '测试商户',
            'mch_id' => 'mch_test',
            'secret' => 'secret_test',
            'status' => true,
        ]);

        $this->mock(WechatPaymentService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('sendRedpack')
                ->once()
                ->withArgs(fn ($payment, $openid, $amount, $billNo): bool => $billNo === 'RP-EXISTING' && $amount === 1000)
                ->andReturn(new Collection(['out_bill_no' => 'RP-EXISTING']));
        });

        $job = Mockery::mock(SendRedpackJob::class, [$code])->makePartial();
        $job->shouldAllowMockingProtectedMethods();
        $job->shouldReceive('getWechatOpenid')->andReturn('openid');
        $job->shouldReceive('getWechatPayment')->andReturn($payment);

        $job->handle();

        $this->assertSame(RedpackCodeStatus::Sent, $code->fresh()->status);
        $this->assertSame('RP-EXISTING', $code->fresh()->bill_no);

        // 已发放的码再次执行任务不再调用通道
        $this->mock(WechatPaymentService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('sendRedpack');
        });

        (new SendRedpackJob($code->fresh()))->handle();
    }

    public function test_redpack_job_uses_redpack_tenant_payment_config(): void
    {
        $activityTenant = Tenant::factory()->create();
        $userTenant = Tenant::factory()->create();

        $user = User::factory()->create();
        // 用户先归属另一租户，打款仍必须使用活动所属租户的商户配置
        $user->tenants()->attach($userTenant);

        $redpack = Redpack::factory()->create(['tenant_id' => $activityTenant->getKey()]);
        $code = $redpack->codes()->create([
            'amount' => 10,
            'status' => RedpackCodeStatus::Claimed,
            'user_id' => $user->getKey(),
        ]);

        WechatPayment::create([
            'tenant_id' => $activityTenant->getKey(),
            'wechat_id' => 1,
            'name' => '活动租户商户',
            'mch_id' => 'mch_activity',
            'secret' => 'secret_test',
            'status' => true,
        ]);

        $this->mock(WechatPaymentService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('sendRedpack')
                ->once()
                ->withArgs(fn ($payment, $openid, $amount, $billNo): bool => $payment->mch_id === 'mch_activity' && $amount === 1000)
                ->andReturn(new Collection(['out_bill_no' => 'RP-TENANT']));
        });

        $job = Mockery::mock(SendRedpackJob::class, [$code])->makePartial();
        $job->shouldAllowMockingProtectedMethods();
        $job->shouldReceive('getWechatOpenid')->andReturn('openid');

        $job->handle();

        $this->assertSame(RedpackCodeStatus::Sent, $code->fresh()->status);
    }

    /**
     * @return array{0: Redpack, 1: User}
     */
    private function fixture(): array
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $user->tenants()->attach($tenant);

        return [
            Redpack::factory()->create(['tenant_id' => $tenant->getKey()]),
            $user,
        ];
    }
}
