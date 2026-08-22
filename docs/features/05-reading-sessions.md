# Feature 05 — Reading sessions

## Goal

A session is one sitting with a book that is `reading`. Start asks for the start page. Stop asks for the page you reached. Pause freezes the timer without closing the session. `library_items.current_page` is only the end page of the last stopped session — it is not edited by hand.

## Out of scope

- Calendar / heatmap
- Pages-per-hour charts
- Two sessions at once
- Starting a session on `want` or `finished` (mark `reading` first)
- UI i18n
- Mobile hamburger

## Data

`reading_sessions`

| Column | Rules |
|--------|--------|
| `user_id` | FK → users, cascade |
| `library_item_id` | FK → library_items, cascade |
| `started_at` | required |
| `ended_at` | null while open (including paused) |
| `start_page` | entered at start |
| `end_page` | null while open; set on stop. Cancel leaves it null |
| `paused_at` | null unless currently paused |
| `paused_seconds` | completed pause time, default 0 |

One **open** session per user (`ended_at is null`).

`pages_read` = `end_page - start_page`. Duration = time from start to end (or now / pause instant), minus `paused_seconds`.

`library_items.current_page` updates only on stop.

## Progress (website)

Not editable.

| Status | UI |
|--------|----|
| `want` | nothing |
| `reading` | slider + percent when `page_count` is set (`current_page / page_count`) |
| `finished` | no slider; “100% · Finished” |

Shown on the shelf card and the book page.

## Actions

| Action | Rules |
|--------|--------|
| `StartReadingSession` | Item owned, `reading`, no open session; `start_page` ≥ 0 and ≤ `page_count` if set |
| `StopReadingSession` | Open session owned; `end_page` ≥ `start_page`; ≤ `page_count` if set. Folds an open pause, then sets `ended_at`, `end_page`, `current_page` |
| `PauseReadingSession` | Open running session. Sets `paused_at` |
| `ResumeReadingSession` | Open paused session. Adds elapsed pause to `paused_seconds`, clears `paused_at` |
| `CancelReadingSession` | Closes without changing the page |
| `ListReadingSessions` | Owner only; optional `library_item_id`; optional stopped-only filter |

## Website

On the book page (if on the shelf and `reading`):

- **Start reading** → dialog: start page → start
- Open → live timer (frozen while paused), **Pause** / **Resume**, **Stop** → dialog: end page, **Cancel**
- No page number field

## Bot

- `/read` — reading books; open session shows elapsed + Pause/Resume + Stop / Cancel
- After Start / Stop the bot asks for the page
- Callbacks: `read:start:{id}`, `read:stop:{id}`, `read:pause:{id}`, `read:resume:{id}`, `read:cancel:{id}`

## Tests

- Start uses the given start page
- Second start rejected
- Stop updates `current_page`
- Pause freezes duration; resume continues
- Cancel does not change the page
- HTTP start (with page) / pause / stop
- Bot start-with-page + stop-with-page
