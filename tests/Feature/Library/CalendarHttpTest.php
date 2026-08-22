<?php

namespace Tests\Feature\Library;

use App\Domain\Books\Models\LibraryItem;
use App\Domain\Books\Models\ReadingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CalendarHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->travelTo(Carbon::parse('2026-08-15 12:00:00'));
    }

    public function test_guests_cannot_view_the_calendar(): void
    {
        $this->get(route('calendar.index'))->assertRedirect(route('login'));
    }

    public function test_the_calendar_lists_every_day_of_the_requested_month(): void
    {
        $user = User::factory()->create();
        $item = LibraryItem::factory()->for($user)->reading()->create();

        ReadingSession::factory()->for($user)->for($item)->stopped(20)->create([
            'start_page' => 10,
            'started_at' => Carbon::parse('2026-08-02 10:00:00'),
            'ended_at' => Carbon::parse('2026-08-02 11:00:00'),
        ]);

        $this->actingAs($user)
            ->get(route('calendar.index', ['month' => '2026-08']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('calendar/Index')
                ->where('month', '2026-08')
                ->where('summary.sessions', 1)
                ->where('summary.pages', 10)
                ->where('summary.minutes', 60)
                ->where('summary.pages_per_hour', 10)
                ->has('summary.days', 31)
                ->where('summary.days.1.date', '2026-08-02')
                ->where('summary.days.1.pages', 10),
            );
    }

    public function test_an_invalid_month_falls_back_to_the_current_month(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('calendar.index', ['month' => 'nope']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('month', '2026-08')
                ->has('summary.days', 31),
            );
    }
}
