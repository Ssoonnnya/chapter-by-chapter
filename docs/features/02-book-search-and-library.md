# Feature 02 — Book search and library

## Goal

A signed-in user searches by title in any language and gets suggestions. They can add a book to their library from the website or the linked Telegram bot. The account locale (`en` / `uk`) never restricts search. Russian editions are never shown or stored.

## Out of scope

- Shelf statuses (want / reading / finished)
- Current page, timer, calendar, pages-per-hour
- Stored en/uk translations of a title
- Telegram inline mode
- UI i18n
- Registration only from the bot

## Actors

| Actor | Can search / add |
|-------|------------------|
| Website user | Yes |
| Linked Telegram user | Yes (`/search`, Add, `/library`) |
| Guest bot user | Guest reply only |

## Data model

```
books
  id
  google_volume_id   unique
  isbn_13            nullable, indexed
  title              as returned by the provider (edition language)
  authors            JSON string list
  description        nullable, shortened
  cover_url          nullable
  language           ISO code from the provider — never ru / rus
  page_count         nullable
  published_year     nullable
  timestamps

library_items
  user_id            FK → users, cascade delete
  book_id            FK → books, cascade delete
  timestamps
  unique(user_id, book_id)
```

`users.locale` is UI only. Book `language` is the edition language and is not limited to `en`/`uk`.

## Search rules

- Query is sent to Google Books as typed. No `langRestrict` from the user locale.
- Results with `language` `ru` or `rus` are dropped before display and before persist.
- Queries shorter than 2 characters do not hit the provider.
- Suggestions: limit 8. Full search (bot `/search`): limit 8. Website list can request up to 20.
- Empty filtered set is an empty state, not an error.

## Actions

| Action | In | Out |
|--------|----|-----|
| `SearchBooks` | query, limit | `list<BookCard>` |
| `ImportAndAddBook` | `User`, Google volume id | book + `alreadyInLibrary` |
| `ListLibrary` | `User` | library items with books |

`ImportAndAddBook` upserts `books`, then `firstOrCreate` on `library_items`. A Russian volume id is rejected and not stored. A second add of the same book is a no-op.

Website and bot call these actions only.

## Website

- `/library` — search field with typeahead + list of added books.
- `GET /library/suggestions?q=` — JSON cards.
- `POST /library` `{ google_volume_id }` — add.

## Bot

- `/search <title>` — numbered results and Add buttons (`callback_data`: `add:<volumeId>`).
- `/search` without a query — usage hint.
- `/library` — titles already added.
- Unlinked Telegram user — existing guest reply.

## Security

- Google API key stays server-side.
- Search is rate limited (`book-search`, 30/minute per user).
- Russian language is filtered in the provider mapper and again in `ImportAndAddBook`.
- Telegram webhook secret is unchanged.

## Local development

Set `GOOGLE_BOOKS_API_KEY` in `.env` (optional for Google’s public quota; required for reliable quota). Tests use `FakeBookProvider` and do not call the network.

## Tests

- Search does not send `langRestrict`
- `ru` / `rus` volumes are excluded
- One-character query does not call the provider
- Add creates `books` + `library_items`
- Second add does not duplicate
- Adding a `ru` volume throws; no rows
- Guest cannot search/add on the website
- Bot `/search` without identity → guest
- Bot `/search` with identity → titles from the fake provider
- Callback Add creates a library item
