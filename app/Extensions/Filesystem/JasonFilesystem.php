<?php

namespace App\Extensions\Filesystem;

use App\Extensions\Filesystem\Adapters\OssAdapter;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;

class JasonFilesystem
{
    public static function boot(): void
    {
        Storage::extend('oss', static function(Application $app, array $config) {
            $adapter = new OssAdapter($config);

            return new FilesystemAdapter(
                new Filesystem($adapter),
                $adapter,
                $config
            );
        });
    }
}
