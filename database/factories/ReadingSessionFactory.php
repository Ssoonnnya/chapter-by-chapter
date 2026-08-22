<?php

namespace Database\Factories;

use App\Domain\Books\Models\LibraryItem;
use App\Domain\Books\Models\ReadingSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReadingSession>
 */
class ReadingSessionFactory extends Factory
{
    protected $model = ReadingSession::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'library_item_id' => LibraryItem::factory()->reading(),
            'started_at' => now(),
            'ended_at' => null,
            'paused_at' => null,
            'paused_seconds' => 0,
            'start_page' => 0,
            'end_page' => null,
        ];
    }

    public function stopped(int $endPage = 10): static
    {
        return $this->state(fn (array $attributes): array => [
            'ended_at' => now(),
            'end_page' => $endPage,
            'start_page' => $attributes['start_page'] ?? 0,
        ]);
    }
}
