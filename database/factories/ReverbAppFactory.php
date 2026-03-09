<?php

namespace Database\Factories;

use App\Models\ReverbApp;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ReverbApp>
 */
class ReverbAppFactory extends Factory
{
    protected $model = ReverbApp::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'app_id' => 'app-'.Str::random(8),
            'app_key' => 'key-'.Str::random(16),
            'app_secret' => 'secret-'.Str::random(32),
            'description' => fake()->sentence(),
            'is_active' => true,
            'max_connections' => 1000,
            'allowed_origins' => ['*'],
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
