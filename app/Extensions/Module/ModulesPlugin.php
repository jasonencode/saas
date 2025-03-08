<?php

namespace App\Extensions\Module;

use Coolsam\Modules\Facades\FilamentModules;
use Coolsam\Modules\ModulesPlugin as BaseModulesPlugin;
use Nwidart\Modules\Facades\Module;

class ModulesPlugin extends BaseModulesPlugin
{
    protected function getModulePlugins(): array
    {
        if (!config('filament-modules.auto-register-plugins', false)) {
            return [];
        }

        $enabledModules = Module::allEnabled();
        $pluginPaths = [];
        foreach ($enabledModules as $module) {
            $path = $module->getPath().DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Filament'.DIRECTORY_SEPARATOR.'*Plugin.php';
            $modulePluginPaths = glob($path);
            if (is_array($modulePluginPaths)) {
                $pluginPaths = array_merge($pluginPaths, $modulePluginPaths);
            }
        }

        return collect($pluginPaths)
            ->map(fn($path) => FilamentModules::convertPathToNamespace($path))
            ->filter()->toArray();
    }
}