<?php

namespace Tests\Unit\Console\Commands\Maintenance;

use App\Console\Commands\Maintenance\CleanUnusedFilesCommand;

class CleanUnusedFilesCommandTestable extends CleanUnusedFilesCommand
{
    public function looksExternal(string $path): bool
    {
        return parent::looksExternal($path);
    }

    public function isExcludedDirectory(string $path): bool
    {
        return parent::isExcludedDirectory($path);
    }

    public function isHiddenFile(string $path): bool
    {
        return parent::isHiddenFile($path);
    }

    public function collectFieldPaths(mixed $value): void
    {
        parent::collectFieldPaths($value);
    }

    public function collectRichTextPaths(mixed $value): void
    {
        parent::collectRichTextPaths($value);
    }

    /**
     * @return array<string, true>
     */
    public function getReferencedPaths(): array
    {
        return $this->referencedPaths;
    }

    public function humanBytes(int $bytes): string
    {
        return parent::humanBytes($bytes);
    }
}
