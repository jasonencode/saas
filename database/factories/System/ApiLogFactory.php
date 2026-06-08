<?php

namespace Database\Factories\System;

use App\Enums\System\HttpMethod;
use App\Models\System\ApiLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApiLog>
 */
class ApiLogFactory extends Factory
{
    protected $model = ApiLog::class;

    public function definition(): array
    {
        return [
            'method' => HttpMethod::GET,
            'path' => '/api/'.$this->faker->word(),
            'ip' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'status_code' => $this->faker->randomElement([200, 201, 301, 400, 404, 500]),
            'duration' => $this->faker->numberBetween(10, 5000),
            'input' => null,
            'output' => null,
        ];
    }

    /**
     * 设置日志创建时间
     */
    public function createdAt(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $date,
        ]);
    }
}
