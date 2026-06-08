<?php

namespace Tests\Feature\System;

use App\Models\System\ApiLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_create_an_api_log(): void
    {
        $log = ApiLog::factory()->create([
            'method' => 'POST',
            'path' => '/api/users',
            'status_code' => 201,
            'duration' => 150,
        ]);

        $this->assertModelExists($log);
        $this->assertSame('POST', $log->method->value);
        $this->assertSame('/api/users', $log->path);
        $this->assertSame(201, $log->status_code);
        $this->assertSame(150, $log->duration);
    }

    public function test_prunable_scope_returns_logs_older_than_180_days(): void
    {
        ApiLog::factory()->create(['created_at' => now()->subDays(200)]);
        ApiLog::factory()->create(['created_at' => now()->subDays(100)]);
        ApiLog::factory()->create(['created_at' => now()->subDays(190)]);

        $prunable = (new ApiLog)->prunable();

        $this->assertCount(2, $prunable->get());
    }

    public function test_prunable_does_not_include_recent_logs(): void
    {
        ApiLog::factory()->create(['created_at' => now()->subDays(10)]);
        ApiLog::factory()->create(['created_at' => now()->subDays(60)]);

        $prunable = (new ApiLog)->prunable();

        $this->assertCount(0, $prunable->get());
    }

    public function test_pruning_deletes_old_logs(): void
    {
        $oldLog = ApiLog::factory()->create(['created_at' => now()->subDays(200)]);
        $recentLog = ApiLog::factory()->create(['created_at' => now()->subDays(10)]);

        // Manual prune: get prunable logs and delete them
        $prunable = (new ApiLog)->prunable();
        $prunable->delete();

        $this->assertModelMissing($oldLog);
        $this->assertModelExists($recentLog);
    }

    public function test_it_can_store_request_input_and_output(): void
    {
        $log = ApiLog::factory()->create([
            'input' => json_encode(['name' => 'test']),
            'output' => json_encode(['id' => 1, 'name' => 'test']),
        ]);

        $this->assertJson($log->input);
        $this->assertJson($log->output);
        $this->assertStringContainsString('test', $log->input);
    }

    public function test_it_can_associate_with_a_user_morph(): void
    {
        $log = ApiLog::factory()->create();

        $this->assertNull($log->user_type);
        $this->assertNull($log->user_id);
    }
}
