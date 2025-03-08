<?php

namespace App\Extensions\Module;

class InstallOption
{
    public bool $migrate_database = false;

    public bool $database_seed = false;

    public bool $remove_database = false;

    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    public function getMigrateDatabase(): bool
    {
        return $this->migrate_database;
    }

    public function setMigrateDatabase(bool $migrate_database): void
    {
        $this->migrate_database = $migrate_database;
    }

    public function getDatabaseSeed(): bool
    {
        return $this->database_seed;
    }

    public function setDatabaseSeed(bool $database_seed): void
    {
        $this->database_seed = $database_seed;
    }

    public function getRemoveDatabase(): bool
    {
        return $this->remove_database;
    }

    public function setRemoveDatabase(bool $remove_database): void
    {
        $this->remove_database = $remove_database;
    }
}