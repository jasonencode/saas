<?php

namespace Tests\Feature\Content;

use App\Models\Content\AppVersion;
use App\Services\Content\AppVersionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppVersionServiceTest extends TestCase
{
    use RefreshDatabase;

    private AppVersionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new AppVersionService;
    }

    public function test_publish_now_sets_publish_at_to_now(): void
    {
        $version = AppVersion::factory()->create(['publish_at' => null]);

        $this->service->publishNow($version);

        $version->refresh();
        $this->assertNotNull($version->publish_at);
        $this->assertTrue($version->publish_at->isToday());
    }

    public function test_schedule_publish_sets_future_date(): void
    {
        $version = AppVersion::factory()->create(['publish_at' => null]);
        $futureDate = now()->addDays(7)->startOfSecond();

        $this->service->schedulePublish($version, $futureDate);

        $version->refresh();
        $this->assertNotNull($version->publish_at);
        $this->assertSame($futureDate->format('Y-m-d H:i:s'), $version->publish_at->format('Y-m-d H:i:s'));
    }

    public function test_schedule_publish_accepts_string_date(): void
    {
        $version = AppVersion::factory()->create(['publish_at' => null]);
        $dateString = '2026-12-25 10:00:00';

        $this->service->schedulePublish($version, $dateString);

        $version->refresh();
        $this->assertNotNull($version->publish_at);
        $this->assertSame('2026-12-25 10:00:00', $version->publish_at->format('Y-m-d H:i:s'));
    }

    public function test_unpublish_clears_publish_at(): void
    {
        $version = AppVersion::factory()->published()->create();

        $this->service->unpublish($version);

        $version->refresh();
        $this->assertNull($version->publish_at);
    }

    public function test_schedule_publish_overrides_existing_publish_at(): void
    {
        $version = AppVersion::factory()->published()->create();
        $newDate = now()->addMonth()->startOfSecond();

        $this->service->schedulePublish($version, $newDate);

        $version->refresh();
        $this->assertSame($newDate->format('Y-m-d H:i:s'), $version->publish_at->format('Y-m-d H:i:s'));
    }
}
