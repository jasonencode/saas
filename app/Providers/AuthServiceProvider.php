<?php

namespace App\Providers;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use ReflectionClass;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(Gate $gate, Filesystem $filesystem): void
    {
        $this->registerPolicies();

        $modelsPath = app_path('Models');
        if ($filesystem->isDirectory($modelsPath)) {
            foreach ($filesystem->allFiles($modelsPath) as $file) {
                $modelClass = 'App\\Models\\'.str_replace(['.php', '/'], ['', '\\'], $file->getRelativePathname());

                if (!class_exists($modelClass)) {
                    continue;
                }

                $reflection = new ReflectionClass($modelClass);
                $attributes = $reflection->getAttributes(UsePolicy::class);

                foreach ($attributes as $attribute) {
                    $policyClass = $attribute->newInstance()->class;
                    if (class_exists($policyClass)) {
                        $gate->policy($modelClass, $policyClass);
                    }
                }
            }
        }
    }
}
