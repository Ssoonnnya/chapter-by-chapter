<?php

namespace Database\Factories;

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
        ];
    }
}
