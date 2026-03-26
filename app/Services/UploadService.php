<?php

namespace App\Services;

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
     * @param  UploadedFile  $file
     * @return array
     */
    public function save(UploadedFile $file): array
    {
        $hash = File::hash($file);
        $name = sprintf('%s.%s', $hash, $file->getClientOriginalExtension());
        $path = sprintf('%s/%s', $this->path, $name);

        $disk = Storage::disk(config('filesystems.default'));

        if (!$disk->putFileAs($this->path, $file, $name)) {
            throw new RuntimeException('文件上传失败', 500);
        }

        return [
            'uuid' => $hash,
            'name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'url' => $disk->url($path),
            'path' => $path,
        ];
    }
}
