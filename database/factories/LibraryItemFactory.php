<?php

namespace Database\Factories;

use App\Domain\Books\Enums\LibraryStatus;
use App\Domain\Books\Models\Book;
use App\Domain\Books\Models\LibraryItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LibraryItem>
 */
class LibraryItemFactory extends Factory
{
    protected $model = LibraryItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'book_id' => Book::factory(),
            'status' => LibraryStatus::Want,
            'current_page' => 0,
        ];
    }

    public function reading(): static
    {
        return $this->state(fn (): array => [
            'status' => LibraryStatus::Reading,
        ]);
    }

    public function finished(): static
    {
        return $this->state(fn (): array => [
            'status' => LibraryStatus::Finished,
        ]);
    }
}
