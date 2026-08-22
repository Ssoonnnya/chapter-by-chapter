<?php

namespace App\Domain\Books\Models;

use Database\Factories\BookFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $google_volume_id
 * @property string|null $isbn_13
 * @property string $title
 * @property list<string> $authors
 * @property string|null $description
 * @property string|null $cover_url
 * @property string|null $language
 * @property int|null $page_count
 * @property int|null $published_year
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable([
    'google_volume_id',
    'isbn_13',
    'title',
    'authors',
    'description',
    'cover_url',
    'language',
    'page_count',
    'published_year',
])]
class Book extends Model
{
    /** @use HasFactory<BookFactory> */
    use HasFactory;

    protected static function newFactory(): BookFactory
    {
        return BookFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'authors' => 'array',
            'page_count' => 'integer',
            'published_year' => 'integer',
        ];
    }

    /**
     * @return HasMany<LibraryItem, $this>
     */
    public function libraryItems(): HasMany
    {
        return $this->hasMany(LibraryItem::class);
    }
}
