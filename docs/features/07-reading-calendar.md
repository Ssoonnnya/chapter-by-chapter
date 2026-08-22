# Feature 07 — Reading calendar and pace

## Goal

Stopped sessions become a month calendar and a pages-per-hour pace. The website and the bot call the same Action. There is no new table — totals are computed from `reading_sessions`.

## Out of scope

- Splitting a session that crosses midnight
- Per-user timezone column
- Goals, streaks, year view
- Extra charts or a GitHub-style heatmap
- Per-book `/stats` in the bot (later)
- Mobile hamburger
- UI i18n

## What counts

Only **stopped** sessions: `ended_at` is set **and** `end_page` is set.

Cancelled sessions (`end_page` null) and open or paused sessions are ignored.

## Day attribution

The calendar day of `ended_at` in `config('app.timezone')`. A sitting that starts Monday night and stops Tuesday morning lands on Tuesday. Sessions are not split across midnight.

## Formulas

- Pages that day = sum(`end_page - start_page`)
- Seconds that day = sum(duration minus pauses)
- Minutes = seconds / 60 (integer)
- Pace = total pages / (total seconds / 3600), only when seconds > 0

## Action

`SummarizeReading`

| In | Rules |
|----|--------|
| `User` | Owner only |
| `month` | `Y-m`, or null for all-time totals (no daily grid) |
| `library_item_id` | Optional. When set, only that book |

Out: totals (sessions, pages, seconds, minutes, pages/hour) and, when a month is given, every day of that month (zeros included for the grid).

## Website

- `GET /calendar?month=Y-m` — month grid, prev/next month. Days with reading are marked (warm beige / dusty rose, not a heatmap). Hover or tap shows pages and minutes. Header totals: sessions, pages, minutes, pages/hour. Missing or invalid `month` falls back to the current month.
- Header: **Calendar** link next to the site name. Library stays home.
- Book page session list and per-session pace: see [feature 08](08-book-session-history.md).

## Bot

- `/stats` in the command menu
- This month, whole library
- Unlinked users get the guest reply
- After adding the command, run `sail artisan telegram:set-commands`

## Tests

- Cancelled and open sessions are excluded
- A sitting is counted on the `ended_at` day
- Pace is omitted when duration is 0
- Month summary and optional book filter
- HTTP `/calendar`
- Book page session history: see feature 08
- Bot `/stats`
