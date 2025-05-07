<?php

namespace App\Extensions\Module;

use App\Models\Module as ModuleModel;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Module;

class DatabaseActivator implements ActivatorInterface
{
    public function enable(Module $module): void
    {
        $this->setActiveByName($module->getName(), true);
    }

    public function setActiveByName(string $name, bool $active): void
    {
        $module = $this->getModuleByName($name);

        ModuleModel::updateOrCreate([
            'name' => $name,
        ], [
            'status' => $active,
            'version' => $module->get('version'),
            'module_id' => strtolower($module->getSnakeName()),
            'alias' => $module->get('alias'),
            'description' => $module->getDescription(),
            'priority' => $module->getPriority(),
            'author' => $module->get('author'),
        ]);
    }

    protected function getModuleByName(string $name): Module
    {
        return \Nwidart\Modules\Facades\Module::find($name);
    }

    public function disable(Module $module): void
    {
        $this->setActiveByName($module->getName(), false);
    }

    public function hasStatus(Module|string $module, bool $status): bool
    {
        $name = $module instanceof Module ? $module->getName() : $module;
        $moduleRecord = ModuleModel::where('name', $name)->first();
        if ($moduleRecord) {
            return $moduleRecord['status'] === $status;
        } else {
            return $status === false;
        }
    }

    public function setActive(Module $module, bool $active): void
    {
        $this->setActiveByName($module->getName(), $active);
    }

    public function delete(Module $module): void
    {
        $moduleRecord = ModuleModel::where('name', $module->getName())->first();
        $moduleRecord?->delete();
    }

    public function reset(): void
    {
        ModuleModel::truncate();
    }
}
