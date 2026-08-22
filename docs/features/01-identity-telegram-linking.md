# Feature 01 — Identity and Telegram linking

## Goal

A person has one website account. Telegram is a linked channel, not a second user. After linking, any bot update can resolve `telegram_id` → `User`, so later features (library, calendar, reading timer) share the same identity on both clients.

## Out of scope

- Registration or login only inside the bot
- Telegram Login Widget on the website
- Books, covers, descriptions
- Reading calendar, timer, pages-per-hour
- Full UI i18n (page copy stays English for now)
- Blocking Russian book content (later books API)

## Supported locales

Application locale is only `en` or `uk`. There is no `ru` locale.

Telegram may send `language_code=ru`. That value may be stored on `telegram_identities.language_code` (it is Telegram’s UI language), but it is never copied onto `users.locale`.

## Actors

| Actor | Meaning |
|-------|---------|
| Website user | Authenticated Fortify user |
| Linked Telegram user | Telegram account with a `telegram_identities` row |
| Guest bot user | Telegram account with no identity |

## Data model

```
users
  id
  name
  email
  password
  locale          enum: en | uk, default en
  ...

telegram_identities          1:1 with users
  user_id         unique FK → users, cascade delete
  telegram_id     unique bigint  (only trusted Telegram identifier)
  username        nullable, never used for auth
  first_name      nullable
  last_name       nullable
  language_code   nullable
  linked_at
  timestamps

telegram_link_tokens
  user_id         FK → users, cascade delete
  token_hash      unique sha256 of the plaintext token
  expires_at      default TTL 10 minutes
  used_at         nullable (used or invalidated)
  created_at
```

Plaintext tokens are never stored. Username is never a uniqueness key.

## Sequence — website → bot

1. Authenticated user opens Settings → Telegram.
2. `GenerateTelegramLinkToken` invalidates unused tokens for that user and creates a new hashed token.
3. Website shows `https://t.me/<bot_username>?start=<plain_token>`.
4. User opens the deep link.
5. Bot receives `/start <token>` and calls `LinkTelegramIdentity`.
6. Identity is created in a transaction; token is marked used.
7. Bot replies that the account is connected.
8. Later updates call `ResolveUserByTelegramId`.

## Sequence — bot without a token

- No identity: guest message + website URL. No user data.
- Identity exists: welcome back with the website `name`.

## Unlink

`UnlinkTelegramIdentity` deletes the identity and invalidates pending tokens for that user. The bot becomes a guest again. The action always uses the authenticated website user — there is no identity id in the URL.

## Domain errors

| Exception | When |
|-----------|------|
| `TelegramLinkTokenInvalid` | Unknown, expired, or already used token |
| `TelegramAlreadyLinked` | This `telegram_id` belongs to another user |
| `UserAlreadyHasTelegram` | This website user already has an identity (unlink first) |
| `TelegramBotUsernameMissing` | Deep link cannot be built because the bot username is not configured |

## Security

- Token at rest is `hash('sha256', $plain)`.
- TTL is 10 minutes (`config('telegram.link_token_ttl_minutes')`).
- Generating a new token invalidates previous unused tokens for that user.
- Generate endpoint is rate limited (`telegram-link`, 5/minute per user).
- Webhook requires `X-Telegram-Bot-Api-Secret-Token` to match `TELEGRAM_WEBHOOK_SECRET`. Missing or wrong secret → 403. CSRF is not used on the webhook.
- Do not trust `username` for identity.
- Bot token stays server-side. The browser only receives a `t.me` URL.

## Local development

1. Create a bot with BotFather. Set `TELEGRAM_BOT_TOKEN`, `TELEGRAM_BOT_USERNAME`, and `TELEGRAM_WEBHOOK_SECRET` in `.env`.
2. Webhook (needs a public HTTPS URL):

   ```bash
   php artisan telegram:set-webhook
   ```

   This calls Telegram `setWebhook` with `TELEGRAM_WEBHOOK_URL` and `TELEGRAM_WEBHOOK_SECRET`.

3. Long polling (no public URL):

   ```bash
   php artisan telegram:poll
   ```

   Use `--once` to fetch a single batch and exit.

   Polling does not use the webhook secret middleware.

## Code map

| Layer | Responsibility |
|-------|----------------|
| `App\Domain\Identity\Actions\*` | Link / unlink / resolve — no HTTP, no Telegram API |
| `App\Http\Controllers\Settings\TelegramLinkController` | Website settings |
| `App\Http\Controllers\Telegram\WebhookController` | Secret-checked webhook entry |
| `App\Telegram\TelegramWebhookProcessor` + `StartHandler` | Map `/start` → actions |
| `App\Telegram\Contracts\TelegramMessenger` | Outbound messages (`Http` in prod, `Fake` in tests) |

Nutgram was not used: it requires Guzzle 7, Laravel 13 ships Guzzle 8.

## Tests

Run the suite with `composer test` (Pint, PHPStan, PHPUnit).

Covered cases:

- `Locale` has only `en` and `uk`; `tryFrom('ru')` is null
- Generated token hash is stored; plaintext is not
- `ResolveUserByTelegramId` found / not found
- Guest cannot generate a link (401)
- Authenticated generate returns a `t.me` URL and ~10 minute expiry
- Regenerating invalidates the previous token
- Generate is rate limited
- Valid `/start <token>` creates the identity
- Reused or expired token fails; identity is not duplicated
- `telegram_id` already taken → `TelegramAlreadyLinked`
- User already linked → `UserAlreadyHasTelegram`
- `/start` without payload, no identity → guest reply
- `/start` without payload, linked → welcome with `user.name`
- Webhook without / with wrong secret → 403
- Unlink removes the identity; bot is guest again
- Unlink uses only `auth()->user()`
