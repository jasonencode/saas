<?php

namespace Tests\Feature\Campaign;

use App\Enums\Campaign\RedpackCodeStatus;
use App\Models\Campaign\Redpack;
use App\Services\Campaign\RedpackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedpackServiceTest extends TestCase
{
    use RefreshDatabase;

    private RedpackService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RedpackService::class);
    }

    // ========================================
    // createCodesBulk - 批量创建红包码
    // ========================================

    public function test_create_codes_bulk_creates_correct_count(): void
    {
        $redpack = Redpack::factory()->create();

        $created = $this->service->createCodesBulk($redpack, 5, 10.00);

        $this->assertEquals(5, $created);
        $this->assertEquals(5, $redpack->codes()->count());
    }

    public function test_create_codes_bulk_sets_correct_amount(): void
    {
        $redpack = Redpack::factory()->create();

        $this->service->createCodesBulk($redpack, 3, 25.50);

        $redpack->codes()->each(function ($code) {
            $this->assertEquals(25.50, $code->amount);
        });
    }

    public function test_create_codes_bulk_sets_active_status(): void
    {
        $redpack = Redpack::factory()->create();

        $this->service->createCodesBulk($redpack, 2, 5.00);

        $redpack->codes()->each(function ($code) {
            $this->assertEquals(RedpackCodeStatus::Active, $code->status);
        });
    }

    public function test_create_codes_bulk_generates_unique_codes(): void
    {
        $redpack = Redpack::factory()->create();

        $this->service->createCodesBulk($redpack, 10, 1.00);

        $codes = $redpack->codes()->pluck('code')->toArray();
        $uniqueCodes = array_unique($codes);

        $this->assertCount(10, $uniqueCodes);
    }

    public function test_create_codes_bulk_with_zero_count(): void
    {
        $redpack = Redpack::factory()->create();

        $created = $this->service->createCodesBulk($redpack, 0, 10.00);

        $this->assertEquals(0, $created);
        $this->assertEquals(0, $redpack->codes()->count());
    }
}
