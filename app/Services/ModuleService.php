<?php

namespace App\Services;

use App\Models\Module;
use Illuminate\Pagination\LengthAwarePaginator;

class ModuleService
{
    public function getModules(int $page, int $recordsPerPage): LengthAwarePaginator
    {
        $collection = \Nwidart\Modules\Facades\Module::toCollection();

        $records = $collection->map(function (\Nwidart\Modules\Laravel\Module $module) {
            return new Module([
                'id' => $module->getName(),
                'name' => $module->getName(),
                'alias' => $module->get('alias'),
                'description' => $module->getDescription(),
                'priority' => $module->getPriority(),
                'active' => $module->isEnabled(),
                'version' => $module->get('version'),
                'author' => $module->get('author'),
                'requires' => $module->get('requires'),
            ]);
        })->forPage($page, $recordsPerPage);

        return new LengthAwarePaginator(
            $records,
            total: $collection->count(),
            perPage: $recordsPerPage,
            currentPage: $page,
        );
    }
}
