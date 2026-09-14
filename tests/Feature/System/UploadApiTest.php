<?php

namespace Tests\Feature\System;

use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UploadApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // 本地 .env 将默认/私有磁盘指向 s3，测试统一使用 fake local 磁盘
        config(['filesystems.default' => 'local']);
        config(['filesystems.private' => 'local']);

        Storage::fake('local');

        $this->user = User::factory()->create();
    }

    // ─── POST /api/system/upload/image ───────────────────────────

    public function test_can_upload_single_image(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/system/upload/image', [
            'file' => UploadedFile::fake()->image('test.jpg', 100, 100),
        ])->assertOk();

        $this->assertNotNull($response->json('url'));
        $this->assertNotNull($response->json('path'));
        $this->assertSame('test.jpg', $response->json('name'));

        Storage::disk('local')->assertExists($response->json('path'));
    }

    public function test_upload_requires_authentication(): void
    {
        $this->postJson('/api/system/upload/image', [
            'file' => UploadedFile::fake()->image('test.jpg'),
        ])->assertUnauthorized();
    }

    public function test_upload_requires_file(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/system/upload/image', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');
    }

    public function test_upload_rejects_non_image_file(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/system/upload/image', [
            'file' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('file');
    }

    public function test_upload_rejects_invalid_visibility(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/system/upload/image', [
            'file' => UploadedFile::fake()->image('test.jpg'),
            'visibility' => 'invalid',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('visibility');
    }

    // ─── POST /api/system/upload/images ──────────────────────────

    public function test_can_upload_multiple_images(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/system/upload/images', [
            'files' => [
                UploadedFile::fake()->image('first.png'),
                UploadedFile::fake()->image('second.png'),
            ],
        ])->assertOk();

        $paths = collect($response->json())->pluck('path');

        $this->assertCount(2, $paths);
        $paths->each(fn (string $path) => Storage::disk('local')->assertExists($path));
    }

    public function test_multiple_upload_requires_files_array(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/system/upload/images', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('files');
    }

    public function test_multiple_upload_rejects_non_image_file(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/system/upload/images', [
            'files' => [
                UploadedFile::fake()->image('ok.png'),
                UploadedFile::fake()->create('bad.txt', 10, 'text/plain'),
            ],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('files.1');
    }

    public function test_guest_cannot_access_upload_endpoints(): void
    {
        $this->postJson('/api/system/upload/images', [])->assertUnauthorized();
    }
}
