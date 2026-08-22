<?php

namespace Database\Factories;

use App\Domain\Books\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    protected $model = Book::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'google_volume_id' => fake()->unique()->bothify('????##########'),
            'isbn_13' => fake()->optional()->numerify('978##########'),
            'title' => fake()->sentence(3),
            'authors' => [fake()->name()],
            'description' => fake()->optional()->paragraph(),
            'cover_url' => fake()->optional()->imageUrl(),
            'language' => fake()->randomElement(['en', 'uk', 'fr', 'de']),
            'page_count' => fake()->optional()->numberBetween(80, 800),
            'published_year' => fake()->optional()->numberBetween(1950, 2026),
        ];
    }
}
