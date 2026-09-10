<?php

namespace App\Services\Foundation;

use App\Contracts\ServiceInterface;
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
     * @param  string  $visibility  可见性：`public`（公开）、`private`（私有，返回临时签名链接）
     *
     * @throws RuntimeException 文件上传失败
     *
     * @return array{uuid: string, name: string, size: int, url: string, path: string} 文件信息
     */
    public function save(UploadedFile $file, string $visibility = 'public'): array
    {
        $hash = File::hash($file);
        $name = sprintf('%s.%s', $hash, $file->getClientOriginalExtension());
        $path = sprintf('%s/%s', $this->path, $name);

        $disk = Storage::disk(config('filesystems.default'));

        if (!$disk->putFileAs($this->path, $file, $name, $visibility)) {
            throw new RuntimeException('文件上传失败', 500);
        }

        return [
            'uuid' => $hash,
            'name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'url' => $visibility === 'private' ? temporary_file_url($path) : $disk->url($path),
            'path' => $path,
        ];
    }
}
