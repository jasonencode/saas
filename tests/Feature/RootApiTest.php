<?php

namespace Tests\Feature;

use App\Enums\Content\PlatformType;
use App\Models\Content\AppVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RootApiTest extends TestCase
{
    use RefreshDatabase;

    // ─── GET /api ─────────────────────────────────────────────────

    public function test_health_check_returns_success(): void
    {
        $response = $this->getJson('/api');

        $response->assertOk();
    }

    // ─── GET /api/app_version ─────────────────────────────────────

    public function test_app_version_endpoint_requires_platform(): void
    {
        $this->getJson('/api/app_version')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['platform']);
    }

    public function test_app_version_endpoint_rejects_invalid_version_format(): void
    {
        $this->getJson('/api/app_version?platform=android&application_id=com.example.app&version=abc')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['version']);
    }

    public function test_app_version_endpoint_returns_the_numerically_latest_version(): void
    {
        $this->makeVersion('1.9.0');
        $this->makeVersion('1.10.0');
        $this->makeVersion('1.2.0');

        $response = $this->getJson('/api/app_version?platform=android&application_id=com.example.app&version=1.0.0');

        $response->assertOk()
            ->assertJsonPath('version', '1.10.0')
            ->assertJsonPath('update', true);
    }

    public function test_app_version_endpoint_includes_immediately_published_versions(): void
    {
        $this->makeVersion('2.0.0', immediate: true);

        $response = $this->getJson('/api/app_version?platform=android&application_id=com.example.app&version=1.0.0');

        $response->assertOk()
            ->assertJsonPath('version', '2.0.0');
    }

    public function test_app_version_endpoint_excludes_versions_scheduled_in_the_future(): void
    {
        $this->makeVersion('3.0.0', publishAt: now()->addDay());

        $response = $this->getJson('/api/app_version?platform=android&application_id=com.example.app&version=1.0.0');

        $response->assertOk()
            ->assertJsonPath('update', false);
    }

    private function makeVersion(string $version, ?Carbon $publishAt = null, bool $immediate = false): AppVersion
    {
        return AppVersion::create([
            'platform' => PlatformType::Android,
            'application_id' => 'com.example.app',
            'version' => $version,
            'description' => [['item' => '修复问题']],
            'download_url' => 'https://example.com/app.apk',
            'publish_at' => $immediate ? null : ($publishAt ?? now()->subMinute()),
            'force' => false,
        ]);
    }
}
