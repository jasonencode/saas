<?php

namespace App\Extensions\Filesystem;

use App\Extensions\Filesystem\Adapters\OssAdapter;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;

class JasonFilesystem
{
    public static function registerFilesystem(): void
    {
        Storage::extend('oss', function(Application $app, array $config) {
            $adapter = new OssAdapter($config);

            return new FilesystemAdapter(
                new Filesystem($adapter),
                $adapter,
                $config
            );
        });
    }
}
