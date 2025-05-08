<?php

namespace App\Extensions\Module;

use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Module;

class RedisActivator implements ActivatorInterface
{
    public function enable(Module $module): void
    {
        // TODO: Implement enable() method.
    }

    public function disable(Module $module): void
    {
        // TODO: Implement disable() method.
    }

    public function hasStatus(Module $module, bool $status): bool
    {
        // TODO: Implement hasStatus() method.
    }

    public function setActive(Module $module, bool $active): void
    {
        // TODO: Implement setActive() method.
    }

    public function setActiveByName(string $name, bool $active): void
    {
        // TODO: Implement setActiveByName() method.
    }

    public function delete(Module $module): void
    {
        // TODO: Implement delete() method.
    }

    public function reset(): void
    {
        // TODO: Implement reset() method.
    }
}