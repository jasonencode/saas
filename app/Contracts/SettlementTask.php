<?php

namespace App\Contracts;

use App\Models\Finance\Task;
use Closure;

interface SettlementTask
{
    public function __construct(Task $task);

    public function getDefaultOptions(): array;

    public function getTitle(): string;

    public function getDescription(): string;

    public function handle(SettleTaskData $data, Closure $next): mixed;
}
