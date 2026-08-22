# Feature 08 — Book session history

## Goal

The book page shows every stopped sitting for that book. Pace is per session, not an all-time average.

## Out of scope

- Editing or deleting a past session
- Charts
- Bot per-book `/stats`
- Calendar changes (month totals stay aggregated)

## What counts

Same as feature 07: only **stopped** sessions (`ended_at` and `end_page` set). Cancelled and open/paused sessions are not listed (the open one already has the timer on the page).

Newest first (`ended_at`).

## Pace

For one session: pages (`end_page - start_page`) / (duration minus pauses / 3600). Omitted when duration is 0.

## Actions

`ListReadingSessions` — owner only; optional `library_item_id`; optional stopped-only filter.

## Website

Below the book (cover + details), a **Reading sessions** block when the book is on the shelf and has at least one stopped session.

Each row: date, page range, pages, minutes, pages/hour.

The all-time one-liner from feature 07 is removed.

## Tests

- Unshelved book has an empty session list
- Cancelled and open sessions are omitted
- Two stopped sessions each have their own pace
