<?php

namespace App\Services;

use App\Models\Attachment;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class UploadService
{
    protected string $path;

    public function __construct()
    {
        $this->path = date('Y/m/d');
    }

    /**
     * @throws Exception
     */
    public function save(UploadedFile $file): array
    {
        $hash = File::hash($file);
        $name = sprintf('%s.%s', $hash, $file->getClientOriginalExtension());
        $path = sprintf('%s/%s', $this->path, $name);

        $existFile = Attachment::where('hash', $hash)->first();

        if (!$existFile) {
            if (Storage::putFileAs($this->path, $file, $name) === false) {
                throw new Exception('文件上传失败', 500);
            }

            $attachment = Attachment::create([
                'name' => $file->getClientOriginalName(),
                'hash' => $hash,
                'extension' => $file->getClientOriginalExtension(),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
                'disk' => config('filesystems.default'),
                'path' => $path,
            ]);

            return [
                'uuid' => $attachment->getKey(),
                'name' => $file->getClientOriginalName(),
                'size' => File::size($file),
                'url' => Storage::url($path),
                'path' => $path,
            ];
        } else {
            return [
                'uuid' => $existFile->getKey(),
                'name' => $existFile->name,
                'size' => $existFile->size,
                'url' => Storage::disk($existFile->disk)->url($existFile->path),
                'path' => $existFile->path,
            ];
        }
    }
}