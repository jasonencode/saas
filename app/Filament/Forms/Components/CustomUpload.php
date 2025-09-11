<?php

namespace App\Filament\Forms\Components;

use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;

class CustomUpload
{
    public static function make(string $field = 'cover'): FileUpload
    {
        return FileUpload::make($field)
            ->directory(function() {
                $tenant = Filament::getTenant();

                if ($tenant) {
                    return $tenant->getKey().'/'.date('Y/m/d');
                }

                return '0/'.date('Y/m/d');
            })
            ->moveFiles()
            ->orientImagesFromExif(false)
            ->fetchFileInformation(false);
    }
}
