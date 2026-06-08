<?php

namespace Database\Factories\Content;

use App\Enums\Content\PlatformType;
use App\Models\Content\AppVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppVersion>
 */
class AppVersionFactory extends Factory
{
    protected $model = AppVersion::class;

    public function definition(): array
    {
        return [
            'platform' => PlatformType::Android,
            'application_id' => 'com.example.app',
            'version' => $this->faker->numerify('#.#.#'),
            'force' => false,
            'description' => ['新增功能', '修复bug'],
            'download_url' => $this->faker->url(),
            'publish_at' => null,
        ];
    }

    /**
     * 已发布
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'publish_at' => now()->subDay(),
        ]);
    }

    /**
     * 强制更新
     */
    public function forced(): static
    {
        return $this->state(fn (array $attributes) => [
            'force' => true,
        ]);
    }
}
