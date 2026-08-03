<?php

namespace App\Support\Filesystem;

use App\Support\Filesystem\Adapters\OssAdapter;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;

/**
 * 文件系统扩展注册
 *
 * 注册 OSS 存储驱动到 Laravel 文件系统。
 */
class JasonFilesystem
{
    /**
     * 初始化文件系统
     */
    public static function boot(): void
    {
        Storage::extend('oss', static function (Application $app, array $config) {
            $adapter = new OssAdapter($config);

            return new FilesystemAdapter(
                new Filesystem($adapter),
                $adapter,
                $config
            );
        });
    }
}
