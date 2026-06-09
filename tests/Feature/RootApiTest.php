<?php

namespace Tests\Feature;

use Tests\TestCase;

class RootApiTest extends TestCase
{
    // ─── GET /api ─────────────────────────────────────────────────

    public function test_health_check_returns_success(): void
    {
        $response = $this->getJson('/api');

        $response->assertOk();
    }

    // ─── GET /api/app_version ─────────────────────────────────────
    // 注意: AppVersionController 使用了 PostgreSQL 特定的 SQL (split_part),
    // 在 SQLite 测试环境中会失败, 跳过此测试。

    public function test_app_version_endpoint_requires_platform(): void
    {
        $this->markTestSkipped('AppVersionController uses PostgreSQL-specific SQL');

        $response = $this->getJson('/api/app_version');

        $response->assertStatus(422);
    }
}
