<?php

namespace App\Models;

use App\Contracts\ModuleSetting;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Builder;
use Nwidart\Modules\Facades\Module;

class Setting extends Model
{
    use Cachable;

    public function scopeOfModule(Builder $query, string $module): void
    {
        $query->where('module', $module);
    }

    protected function getValueAttribute(mixed $value): mixed
    {
        $module = Module::find($this->module);

        if (!$module?->isEnabled()) {
            return $value;
        }

        $settingClass = $this->getModuleSettingClass($module->getName());
        if (!$settingClass) {
            return $value;
        }

        $instance = new $settingClass();
        if ($instance instanceof ModuleSetting) {
            $this->casts = array_merge(
                $this->casts,
                $instance->casts()
            );
        }

        if (in_array($this->key, array_keys($this->casts))) {
            return $this->castAttribute($this->key, $value);
        }

        return $value;
    }

    protected function getModuleSettingClass(string $moduleName): ?string
    {
        $path = module_path($moduleName, 'app/Filament/Setting.php');
        $class = sprintf('Modules\\%s\\Filament\\Setting', $moduleName);

        return file_exists($path) && class_exists($class) ? $class : null;
    }

    protected function setValueAttribute($value): void
    {
        $this->attributes['value'] = $value;
    }
}
