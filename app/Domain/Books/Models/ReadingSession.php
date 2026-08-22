<?php

namespace App\Domain\Books\Models;

use App\Models\User;
use Carbon\CarbonInterface;
use Database\Factories\ReadingSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $library_item_id
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $paused_at
 * @property int $paused_seconds
 * @property int $start_page
 * @property int|null $end_page
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read LibraryItem $libraryItem
 * @property-read User $user
 */
#[Fillable([
    'user_id',
    'library_item_id',
    'started_at',
    'ended_at',
    'paused_at',
    'paused_seconds',
    'start_page',
    'end_page',
])]
class ReadingSession extends Model
{
    /** @use HasFactory<ReadingSessionFactory> */
    use HasFactory;

    protected static function newFactory(): ReadingSessionFactory
    {
        return ReadingSessionFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'paused_at' => 'datetime',
            'paused_seconds' => 'integer',
            'start_page' => 'integer',
            'end_page' => 'integer',
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
     * @return BelongsTo<LibraryItem, $this>
     */
    public function libraryItem(): BelongsTo
    {
        return $this->belongsTo(LibraryItem::class);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('ended_at');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeStopped(Builder $query): Builder
    {
        return $query->whereNotNull('ended_at')->whereNotNull('end_page');
    }

    public static function openFor(User $user): ?self
    {
        return self::query()
            ->open()
            ->where('user_id', $user->id)
            ->with(['libraryItem.book'])
            ->first();
    }

    public function isOpen(): bool
    {
        return $this->ended_at === null;
    }

    public function isPaused(): bool
    {
        return $this->isOpen() && $this->paused_at !== null;
    }

    public function pagesRead(): ?int
    {
        if ($this->end_page === null) {
            return null;
        }

        return $this->end_page - $this->start_page;
    }

    public function pagesPerHour(): ?float
    {
        $seconds = $this->durationSeconds();
        $pages = $this->pagesRead();

        if ($seconds === null || $seconds === 0 || $pages === null) {
            return null;
        }

        return round($pages / ($seconds / 3600), 1);
    }

    /**
     * @return array{id: int, started_at: string, ended_at: string, start_page: int, end_page: int, pages: int, seconds: int, minutes: int, pages_per_hour: float|null}
     */
    public function toHistoryPayload(): array
    {
        $endedAt = $this->ended_at;
        $endPage = $this->end_page;
        $seconds = $this->durationSeconds() ?? 0;

        return [
            'id' => $this->id,
            'started_at' => $this->started_at->toIso8601String(),
            'ended_at' => $endedAt?->toIso8601String() ?? $this->started_at->toIso8601String(),
            'start_page' => $this->start_page,
            'end_page' => $endPage ?? $this->start_page,
            'pages' => $this->pagesRead() ?? 0,
            'seconds' => $seconds,
            'minutes' => intdiv($seconds, 60),
            'pages_per_hour' => $this->pagesPerHour(),
        ];
    }

    public function durationSeconds(): ?int
    {
        if ($this->ended_at === null) {
            return null;
        }

        return $this->elapsedSeconds($this->ended_at);
    }

    public function elapsedSeconds(?CarbonInterface $until = null): int
    {
        $until ??= $this->ended_at ?? $this->paused_at ?? now();

        return max(0, (int) $this->started_at->diffInSeconds($until) - $this->paused_seconds);
    }

    public function closeOpenPause(?CarbonInterface $at = null): void
    {
        if ($this->paused_at === null) {
            return;
        }

        $at ??= now();
        $this->paused_seconds += max(0, (int) $this->paused_at->diffInSeconds($at));
        $this->paused_at = null;
    }

    /**
     * @return array{id: int, library_item_id: int, started_at: string, start_page: int, paused_at: string|null, paused_seconds: int}
     */
    public function toOpenPayload(): array
    {
        return [
            'id' => $this->id,
            'library_item_id' => $this->library_item_id,
            'started_at' => $this->started_at->toIso8601String(),
            'start_page' => $this->start_page,
            'paused_at' => $this->paused_at?->toIso8601String(),
            'paused_seconds' => $this->paused_seconds,
        ];
    }
}
