# Chapter by Chapter — feature docs

Feature contracts live in `docs/features`. Each file describes the goal, data model, flows, security rules, and how to test that slice.

| # | Feature | Status |
|---|---------|--------|
| 01 | [Identity and Telegram linking](features/01-identity-telegram-linking.md) | Implemented |
| 02 | [Book search and library](features/02-book-search-and-library.md) | Implemented |

Website and Telegram bot are two clients of the same application actions. Domain rules do not live in HTTP controllers or bot handlers.
