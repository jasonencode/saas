<?php

namespace Tests\Feature\User;

use App\Enums\User\RealnameStatus;
use App\Enums\User\RealnameType;
use App\Models\User\User;
use App\Models\User\UserRealname;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RealnameApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    /**
     * 合法的大陆身份证号（校验码已验证）
     */
    private const string ID_CARD = '110105199001010010';

    protected function setUp(): void
    {
        parent::setUp();

        // 本地 .env 将私有磁盘指向 s3-private，测试统一使用 local 磁盘
        config(['filesystems.private' => 'local']);

        $this->user = User::factory()->create();
    }

    // ─── GET /api/user/realname ──────────────────────────────────

    public function test_index_returns_empty_when_never_submitted(): void
    {
        Sanctum::actingAs($this->user);

        $this->getJson('/api/user/realname')
            ->assertOk()
            ->assertJsonPath('code', 0);
    }

    public function test_index_returns_latest_record(): void
    {
        // user_realnames 对 (user_id, type) 有唯一约束，用两种类型构造两条记录
        $this->makeRealname(status: RealnameStatus::Approved, type: RealnameType::Enterprise);
        $latest = $this->makeRealname(status: RealnameStatus::Pending, type: RealnameType::Personal);

        Sanctum::actingAs($this->user);

        $this->getJson('/api/user/realname')
            ->assertOk()
            ->assertJsonPath('realname_id', $latest->id);
    }

    // ─── GET /api/user/realname/status ───────────────────────────

    public function test_status_returns_null_fields_when_never_submitted(): void
    {
        Sanctum::actingAs($this->user);

        $this->getJson('/api/user/realname/status')
            ->assertOk();
    }

    public function test_status_returns_pending_state(): void
    {
        $this->makeRealname(status: RealnameStatus::Pending);

        Sanctum::actingAs($this->user);

        $this->getJson('/api/user/realname/status')
            ->assertOk()
            ->assertJsonPath('value', RealnameStatus::Pending->value);
    }

    // ─── POST /api/user/realname ─────────────────────────────────

    public function test_can_submit_personal_realname(): void
    {
        Storage::fake('local');
        Sanctum::actingAs($this->user);

        $this->postJson('/api/user/realname', [
            'type' => RealnameType::Personal->value,
            'name' => '张三',
            'id_card_number' => self::ID_CARD,
            'id_card_front' => $this->makePrivateFile('front.jpg'),
            'id_card_back' => $this->makePrivateFile('back.jpg'),
        ])->assertCreated()
            ->assertJsonPath('status', RealnameStatus::Pending->value);

        $this->assertDatabaseHas('user_realnames', [
            'user_id' => $this->user->id,
            'type' => RealnameType::Personal->value,
            'status' => RealnameStatus::Pending->value,
        ]);
    }

    public function test_submit_personal_requires_all_fields(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/user/realname', [
            'type' => RealnameType::Personal->value,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_submit_personal_validates_id_card_format(): void
    {
        Storage::fake('local');
        Sanctum::actingAs($this->user);

        $this->postJson('/api/user/realname', [
            'type' => RealnameType::Personal->value,
            'name' => '张三',
            'id_card_number' => '110105199001010011',
            'id_card_front' => $this->makePrivateFile('front.jpg'),
            'id_card_back' => $this->makePrivateFile('back.jpg'),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('id_card_number');
    }

    public function test_submit_personal_rejects_missing_file(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/user/realname', [
            'type' => RealnameType::Personal->value,
            'name' => '张三',
            'id_card_number' => self::ID_CARD,
            'id_card_front' => 'not/exists/front.jpg',
            'id_card_back' => 'not/exists/back.jpg',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('id_card_front');
    }

    public function test_can_submit_enterprise_realname(): void
    {
        Storage::fake('local');
        Sanctum::actingAs($this->user);

        $this->postJson('/api/user/realname', [
            'type' => RealnameType::Enterprise->value,
            'name' => '测试科技有限公司',
            'business_license' => $this->makePrivateFile('license.jpg'),
            'contact_person' => '李四',
            'contact_phone' => '13800138000',
        ])->assertCreated()
            ->assertJsonPath('status', RealnameStatus::Pending->value);

        $this->assertDatabaseHas('user_realnames', [
            'user_id' => $this->user->id,
            'type' => RealnameType::Enterprise->value,
        ]);
    }

    public function test_submit_rejects_duplicate_pending_submission(): void
    {
        Storage::fake('local');
        Sanctum::actingAs($this->user);

        $payload = [
            'type' => RealnameType::Personal->value,
            'name' => '张三',
            'id_card_number' => self::ID_CARD,
            'id_card_front' => $this->makePrivateFile('front.jpg'),
            'id_card_back' => $this->makePrivateFile('back.jpg'),
        ];

        $this->postJson('/api/user/realname', $payload)->assertCreated();

        $this->postJson('/api/user/realname', $payload)
            ->assertStatus(400);
    }

    public function test_resubmit_allowed_after_rejection(): void
    {
        Storage::fake('local');
        $this->makeRealname(status: RealnameStatus::Rejected);

        Sanctum::actingAs($this->user);

        $this->postJson('/api/user/realname', [
            'type' => RealnameType::Personal->value,
            'name' => '张三',
            'id_card_number' => self::ID_CARD,
            'id_card_front' => $this->makePrivateFile('front-2.jpg'),
            'id_card_back' => $this->makePrivateFile('back-2.jpg'),
        ])->assertCreated()
            ->assertJsonPath('status', RealnameStatus::Pending->value);
    }

    // ─── 未登录 ──────────────────────────────────────────────────

    public function test_guest_cannot_access_realname_endpoints(): void
    {
        $this->getJson('/api/user/realname')->assertUnauthorized();
        $this->getJson('/api/user/realname/status')->assertUnauthorized();
        $this->postJson('/api/user/realname', [])->assertUnauthorized();
    }

    // ─── 测试夹具 ────────────────────────────────────────────────

    private function makeRealname(RealnameStatus $status, RealnameType $type = RealnameType::Personal): UserRealname
    {
        return UserRealname::create([
            'user_id' => $this->user->id,
            'type' => $type,
            'name' => '张三',
            'id_card_number' => self::ID_CARD,
            'id_card_front' => '0/test/front.jpg',
            'id_card_back' => '0/test/back.jpg',
            'status' => $status,
        ]);
    }

    private function makePrivateFile(string $path): string
    {
        Storage::disk('local')->put($path, 'fake-image');

        return $path;
    }
}
