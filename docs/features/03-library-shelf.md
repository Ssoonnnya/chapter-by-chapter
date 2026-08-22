# Feature 03 — Library shelf and bot command menu

## Goal

A book in the library has a status and a current page. The website and the linked bot change these through the same actions. Telegram shows a Menu of bot commands (`setMyCommands`).

## Out of scope

- Reading timer and sessions
- Calendar and pages-per-hour
- Editing the page number from the bot (shown only)
- UI i18n
- Reply keyboard under the input (Menu commands only)

## Data

`library_items` gains:

| Column | Rules |
|--------|--------|
| `status` | `want` \| `reading` \| `finished`, default `want` |
| `current_page` | unsigned int, default `0`; never above `books.page_count` when that is set |

Adding from search still creates `want` / page `0`.

## Actions

| Action | Rules |
|--------|--------|
| `ListLibrary` | Optional status filter |
| `UpdateLibraryItemStatus` | Item must belong to the user |
| `UpdateLibraryItemPage` | Page ≥ 0; if `page_count` is set, page ≤ `page_count` |

## Website

- Filter tabs: All / Want to read / Reading / Finished (`?status=`)
- Per book: status buttons and a page field
- Progress text when `page_count` exists, e.g. `12 / 310`

## Bot

- `/library` — list with status and pages, filter buttons, status buttons per book
- `/library reading` (or `want` / `finished`) — filtered list
- Callbacks: `lib:f:<status\|all>`, `lib:s:<id>:<status>`
- Unlinked user — guest reply

Command menu (Telegram Menu next to the input):

| command | description |
|---------|-------------|
| `start` | Connect your account |
| `search` | Find a book by title |
| `library` | Your books |

Register once:

```bash
./vendor/bin/sail artisan telegram:set-commands
```

## Tests

- New library item is `want` / page `0`
- Status update is scoped to the owner
- Page above `page_count` is rejected
- `ListLibrary` filter
- HTTP filter + status + page
- Bot `/library` shows status; callback updates status
- `telegram:set-commands` sends the three commands
