# Feature 04 — Reader shell (UI)

## Goal

The signed-in app looks like a reading site, not an admin kit. Library is the home. The header has the site name, book search, a Telegram connect flow (confirm → link + QR), and a name menu (profile / log out). Colors stay warm and light.

## Out of scope

- Mobile hamburger menu (later)
- Dark theme
- Reading timer / calendar
- Changing profile fields beyond the existing settings pages

## Shell

- Default layout is the header layout. The sidebar is unused.
- `fortify.home` and post-login redirect: `/library`.
- `/dashboard` redirects to `/library`.
- Authenticated `/` redirects to `/library`.
- Guests see a short landing in the same palette (Log in / Register).

## Header

- Left: `config('app.name')` as text, links to the library.
- Center/right: book search (same suggestions + add as the library page).
- Telegram control, then the name plaque.
- Name menu: Profile (`/settings/profile`), Log out.
- No Dashboard, Laravel docs, or GitHub links.

## Telegram connect

1. If not linked: click → confirm dialog.
2. Confirm → generate a one-time token (not before).
3. Second dialog: `t.me` URL, copy button, QR of the same URL, expiry, and “After you tap Start in the bot, refresh this page.”
4. If already linked: show Connected / `@username`, no generate.

JSON `POST /settings/telegram` (`Accept: application/json`) returns `{ url, expires_at }` for the header. The settings page keeps the Inertia redirect.

## Theme

Light-only CSS tokens: paper beige background, cream cards, brown text, cocoa primary, dusty rose accent. `initializeTheme` always applies light.

## Tests

- Login / register redirect to `library.index`
- Guest `/` is the landing; auth `/` goes to the library
- `/dashboard` redirects to the library
- JSON Telegram generate returns a `t.me` URL
- Linked user JSON generate is rejected (409)
