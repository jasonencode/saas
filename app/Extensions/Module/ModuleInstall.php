<?php

namespace App\Extensions\Module;

use App\Contracts\InstallableModule;
use Coolsam\Modules\Facades\FilamentModules;
use Nwidart\Modules\Facades\Module;

class ModuleInstall
{
    public static function install(string $moduleName, InstallOption $option): void
    {
        $module = Module::find($moduleName);
        $path = $module->getPath().DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Filament'.DIRECTORY_SEPARATOR.'*Plugin.php';
        $modulePluginPaths = glob($path);

        $plugin = FilamentModules::convertPathToNamespace($modulePluginPaths[0]);

        if (resolve($plugin) instanceof InstallableModule) {
            resolve($plugin)->install($option);
        }
    }
}