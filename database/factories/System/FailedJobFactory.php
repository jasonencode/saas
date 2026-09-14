<?php

namespace Database\Factories\System;

use App\Models\System\FailedJob;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FailedJob>
 */
class FailedJobFactory extends Factory
{
    protected $model = FailedJob::class;

    public function definition(): array
    {
        $exception = new \Exception('Test exception');

        return [
            'uuid' => $this->faker->uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => [
                'job' => 'App\Jobs\TestJob',
                'data' => ['action' => 'test'],
                'id' => $this->faker->uuid(),
                'attempts' => 0,
                'exception' => (string) $exception,
            ],
            'exception' => $exception->getMessage()."\n\n".$exception->getTraceAsString(),
            'failed_at' => now(),
        ];
    }
}
