<?php

namespace App\Services\Foundation;

use App\Contracts\Authenticatable;
use App\Contracts\ServiceInterface;
use App\Enums\Foundation\FileVisibility;
use App\Models\Foundation\UploadFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class UploadService implements ServiceInterface
{
    protected string $path;

    public function __construct()
    {
        $this->path = date('Y/m/d');
    }

    /**
     * 保存文件
     *
     * @param  UploadedFile  $file  上传的文件
     * @param  FileVisibility  $visibility  可见性：公开文件写入默认磁盘，私有文件写入私有磁盘并返回临时签名链接
     * @param  Authenticatable|null  $uploader  上传者，传入时记录上传文件信息并以记录 UUID 作为文件标识
     *
     * @throws RuntimeException 文件上传失败
     *
     * @return array{uuid: string, name: string, size: int, url: string, path: string} 文件信息（uuid 为 upload_files 主键，未记录上传者时为文件内容哈希）
     */
    public function save(UploadedFile $file, FileVisibility $visibility = FileVisibility::Public, ?Authenticatable $uploader = null): array
    {
        $hash = File::hash($file);
        $name = sprintf('%s.%s', $hash, $file->getClientOriginalExtension());
        $path = sprintf('%s/%s', $this->path, $name);

        $disk = Storage::disk($visibility->disk());

        if (!$disk->putFileAs($this->path, $file, $name, $visibility->value)) {
            throw new RuntimeException('文件上传失败', 500);
        }

        $uploadFile = null;

        if ($uploader) {
            $uploadFile = UploadFile::create([
                'user_id' => $uploader->getKey(),
                'original_name' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType() ?: $file->getClientMimeType(),
                'size' => (int) $file->getSize(),
                'disk' => $visibility->disk(),
                'uploaded_at' => now(),
            ]);
        }

        return [
            'uuid' => $uploadFile?->getKey() ?? $hash,
            'name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'url' => $visibility === FileVisibility::Private ? temporary_file_url($path) : $disk->url($path),
            'path' => $path,
        ];
    }
}
