<?php

namespace App\Extensions\Module;

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Artisan;

trait ModuleSetupActions
{
    public function install(?InstallOption $options = null): void
    {
        if ($options->migrate_database) {
            Artisan::call('module:migrate', [
                'module' => $this->getModuleName(),
            ]);
        }
        if ($options->database_seed) {
            Artisan::call('module:seed', [
                'module' => $this->getModuleName(),
            ]);
        }

        if (method_exists($this, 'afterInstall')) {
            call_user_func([$this, 'afterInstall']);
        }

        $this->clearModuleCache();
    }

    protected function clearModuleCache(): void
    {
        foreach (Filament::getPanels() as $panel) {
            $panel->clearCachedComponents();
        }
    }

    public function uninstall(?InstallOption $options = null): void
    {
        if (!is_null($options) && $options->remove_database) {
            Artisan::call('module:migrate-rollback', [
                'module' => $this->getModuleName(),
            ]);
        }

        if (method_exists($this, 'afterUninstall')) {
            call_user_func([$this, 'afterUninstall']);
        }

        $this->clearModuleCache();
    }
}