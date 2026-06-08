<?php

namespace App\Contracts;

use Closure;

interface SettlementTask
{
    public function getDefaultOptions(): array;

    public function getTitle(): string;

    public function getDescription(): string;

    public function handle(SettleTaskData $data, Closure $next): mixed;
}
