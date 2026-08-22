<?php

namespace Database\Factories;

use App\Domain\Identity\Models\TelegramIdentity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TelegramIdentity>
 */
class TelegramIdentityFactory extends Factory
{
    protected $model = TelegramIdentity::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'telegram_id' => fake()->unique()->numberBetween(1_000_000, 2_000_000_000),
            'username' => fake()->unique()->userName(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->optional()->lastName(),
            'language_code' => fake()->randomElement(['en', 'uk']),
            'linked_at' => now(),
        ];
    }
}
