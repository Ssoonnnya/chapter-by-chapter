<?php

namespace App\Http\Controllers;

use App\Domain\Books\Actions\SummarizeReading;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    public function index(Request $request, SummarizeReading $summarizeReading): Response
    {
        $month = $this->month($request);

        return Inertia::render('calendar/Index', [
            'month' => $month,
            'summary' => $summarizeReading->handle($this->user($request), $month)->toArray(),
        ]);
    }

    private function month(Request $request): string
    {
        $month = $request->query('month');

        if (is_string($month) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) === 1) {
            return $month;
        }

        return now()->format('Y-m');
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        return $user;
    }
}
