<?php

namespace Tests\Feature\Foundation;

use Tests\TestCase;

/**
 * Livewire 临时上传磁盘（INF-10）
 */
class TemporaryUploadDiskTest extends TestCase
{
    public function test_livewire_temporary_uploads_default_to_private_disk(): void
    {
        $disk = config('livewire.temporary_file_upload.disk');

        $this->assertSame('local', $disk);
        $this->assertSame(storage_path('app/private'), config("filesystems.disks.{$disk}.root"));
        $this->assertArrayNotHasKey('url', config("filesystems.disks.{$disk}"));
        $this->assertArrayNotHasKey('visibility', config("filesystems.disks.{$disk}"));
    }
}
