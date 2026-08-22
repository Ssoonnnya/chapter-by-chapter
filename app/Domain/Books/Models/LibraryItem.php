<?php

namespace App\Domain\Books\Models;

use App\Domain\Books\Enums\LibraryStatus;
use App\Models\User;
use Database\Factories\LibraryItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $book_id
 * @property LibraryStatus $status
 * @property int $current_page
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Book $book
 * @property-read User $user
 * @property-read Collection<int, ReadingSession> $readingSessions
 */
#[Fillable(['user_id', 'book_id', 'status', 'current_page'])]
class LibraryItem extends Model
{
    /** @use HasFactory<LibraryItemFactory> */
    use HasFactory;

    protected static function newFactory(): LibraryItemFactory
    {
        return LibraryItemFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => LibraryStatus::class,
            'current_page' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Book, $this>
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * @return HasMany<ReadingSession, $this>
     */
    public function readingSessions(): HasMany
    {
        return $this->hasMany(ReadingSession::class);
    }
}
