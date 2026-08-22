# Feature 06 — Book page

## Goal

A book has its own page: cover, full description, and metadata. Search opens that page. Adding to the library is a heart, not a click on the row. Page number and the reading timer live only on the book page, once the book is on the shelf. The shelf keeps status buttons.

## Out of scope

- Calendar / pages-per-hour
- Changing bot Add (still adds immediately)
- Guest book pages
- UI i18n

## Actions

| Action | Rules |
|--------|--------|
| `FindOrImportBook` | Fetch volume, reject `ru`/`rus`, upsert `books`. Does **not** create `library_items` |
| `ImportAndAddBook` | Uses `FindOrImportBook`, then `firstOrCreate` on the shelf |
| `SearchBooks` | Unchanged. HTTP adds `in_library` for the current user |

## Website

- `GET /books/{googleVolume}` — book page. Creates the `books` row if needed.
- Search row → book page. Heart on the row adds (`want`) and stays in the list; filled if already on the shelf.
- Shelf cards link to the book page. No page field, no timer. Status filters and status buttons stay. Open session shows a short “Reading now” note.
- Book page: heart if not on the shelf; if on the shelf — status, progress, start/pause/stop (pages only in session dialogs).
- `POST /library` redirects back (search or book page).

## Tests

- Viewing a volume imports `books` and not `library_items`
- Russian / missing volume is 404
- Suggestions include `in_library`
- Add from the book page stays on the book page
- Library index still lists status and `openSession`, without requiring page/timer on that page
