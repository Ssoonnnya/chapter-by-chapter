<?php

namespace Tests\Feature\Library;

use App\Domain\Books\Actions\SummarizeReading;
use App\Domain\Books\Models\LibraryItem;
use App\Domain\Books\Models\ReadingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SummarizeReadingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_counts_only_stopped_sessions_on_the_ended_at_day(): void
    {
        $user = User::factory()->create();
        $item = LibraryItem::factory()->for($user)->reading()->create();
        $otherItem = LibraryItem::factory()->for($user)->reading()->create();

        ReadingSession::factory()->for($user)->for($item)->stopped(20)->create([
            'start_page' => 10,
            'started_at' => Carbon::parse('2026-07-31 22:00:00'),
            'ended_at' => Carbon::parse('2026-08-01 01:00:00'),
            'paused_seconds' => 0,
        ]);

        ReadingSession::factory()->for($user)->for($item)->create([
            'start_page' => 20,
            'started_at' => Carbon::parse('2026-08-02 10:00:00'),
            'ended_at' => Carbon::parse('2026-08-02 11:00:00'),
            'end_page' => null,
        ]);

        ReadingSession::factory()->for($user)->for($item)->create([
            'start_page' => 20,
            'started_at' => Carbon::parse('2026-08-03 10:00:00'),
            'ended_at' => null,
            'end_page' => null,
        ]);

        ReadingSession::factory()->for($user)->for($otherItem)->stopped(15)->create([
            'start_page' => 5,
            'started_at' => Carbon::parse('2026-08-05 09:00:00'),
            'ended_at' => Carbon::parse('2026-08-05 10:00:00'),
        ]);

        ReadingSession::factory()->stopped(30)->create([
            'start_page' => 0,
            'started_at' => Carbon::parse('2026-08-01 10:00:00'),
            'ended_at' => Carbon::parse('2026-08-01 11:00:00'),
        ]);

        $summary = app(SummarizeReading::class)->handle($user, '2026-08');

        $this->assertSame(2, $summary->sessions);
        $this->assertSame(20, $summary->pages);
        $this->assertSame(14400, $summary->seconds);
        $this->assertSame(240, $summary->minutes());
        $this->assertSame(5.0, $summary->pagesPerHour);
        $this->assertCount(31, $summary->days);

        $first = $summary->days[0];
        $this->assertSame('2026-08-01', $first->date);
        $this->assertSame(1, $first->sessions);
        $this->assertSame(10, $first->pages);
        $this->assertSame(10800, $first->seconds);

        $this->assertSame(0, $summary->days[1]->sessions);
        $this->assertSame(10, $summary->days[4]->pages);

        $july = app(SummarizeReading::class)->handle($user, '2026-07');
        $this->assertSame(0, $july->sessions);
        $this->assertCount(31, $july->days);

        $bookOnly = app(SummarizeReading::class)->handle($user, '2026-08', $item->id);
        $this->assertSame(1, $bookOnly->sessions);
        $this->assertSame(10, $bookOnly->pages);
    }

    public function test_pace_is_omitted_when_duration_is_zero(): void
    {
        $user = User::factory()->create();
        $item = LibraryItem::factory()->for($user)->reading()->create();

        ReadingSession::factory()->for($user)->for($item)->stopped(12)->create([
            'start_page' => 10,
            'started_at' => Carbon::parse('2026-08-10 12:00:00'),
            'ended_at' => Carbon::parse('2026-08-10 12:00:00'),
            'paused_seconds' => 0,
        ]);

        $summary = app(SummarizeReading::class)->handle($user, '2026-08');

        $this->assertSame(1, $summary->sessions);
        $this->assertSame(2, $summary->pages);
        $this->assertSame(0, $summary->seconds);
        $this->assertNull($summary->pagesPerHour);
    }

    public function test_all_time_totals_have_no_daily_grid(): void
    {
        $user = User::factory()->create();
        $item = LibraryItem::factory()->for($user)->reading()->create();

        ReadingSession::factory()->for($user)->for($item)->stopped(20)->create([
            'start_page' => 10,
            'started_at' => Carbon::parse('2026-07-01 10:00:00'),
            'ended_at' => Carbon::parse('2026-07-01 11:00:00'),
        ]);
        ReadingSession::factory()->for($user)->for($item)->stopped(30)->create([
            'start_page' => 20,
            'started_at' => Carbon::parse('2026-08-01 10:00:00'),
            'ended_at' => Carbon::parse('2026-08-01 11:00:00'),
        ]);

        $summary = app(SummarizeReading::class)->handle($user, null, $item->id);

        $this->assertNull($summary->month);
        $this->assertSame(2, $summary->sessions);
        $this->assertSame(20, $summary->pages);
        $this->assertSame([], $summary->days);
    }
}
