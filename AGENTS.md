# Agent Guide — laravel-mutlucell-sms

## Overview
Mutlucell SMS API wrapper for Laravel. Sends XML POST requests to
`smsgw.mutlucell.com` (send, bulk, multi, blacklist, balance, reports).

## Stack
- PHP 8.3, `illuminate/support` 13.*, Guzzle `^7.8.2|^8.0.2`, `ext-simplexml`
- Dev: PHPUnit 12.5, Orchestra Testbench 11 (Laravel harness)

## Layout
- `src/Ardakilic/Mutlucell/Mutlucell.php` — main class (all API logic)
- `src/Ardakilic/Mutlucell/MutlucellServiceProvider.php` — `mutlucell` singleton + translations
- `src/Ardakilic/Mutlucell/Facades/Mutlucell.php` — facade (`mutlucell`)
- `src/config/mutlucell.php` — `auth`, `default_sender`, `queue`, `charset`, `append_unsubscribe_link`
- `src/lang/en/en.php`, `src/lang/tr/tr.php` — `reports`/`sms`/`app`/`exceptions` strings
- `tests/` — `TestCase.php`, `MutlucellTest.php`, `ServiceProviderTest.php`, `LangTest.php`
- `.github/workflows/coverage.yml` — PR coverage report (base vs head)

## Install
- `composer install` (inside Docker; no local PHP needed)

## Tests
- `make test` (preferred) or `docker compose run --rm tests` or `docker compose up --build --abort-on-container-exit --exit-code-from tests`
- Never hit the live API: all HTTP is mocked (Guzzle MockHandler).
- `composer test` = no coverage; `composer test:coverage` = text summary + clover.

## Conventions
- PSR-4 (`Ardakilic\Mutlucell\`). v2 breaks BC intentionally (named args, renames, types) — see CHANGELOG.md; keep config keys stable.
- Keep `en`/`tr` lang files in sync (same top-level and sub-keys).
- Public API is named-arguments-first (PHP 8): descriptive param names (`sender`, `messages`, `receiver`/`recipients`, `phoneNumbers`, `messageId`, `output`), scalar/union types; call sites in tests/README use `name:` syntax.
- No new runtime dependencies.

## Testability
- Inject HTTP: `new Mutlucell($app, $client)` or `setHttpClient($client)`.
- Queue path: `Queue::fake()` + `queue=true` returns `'queued'` with no HTTP.
- Mock HTTP: Guzzle `MockHandler` + `HandlerStack` + history middleware.

## Coverage
- Target: >=90% lines on `Mutlucell.php` (`--coverage-text` summary).

## PR rules
- Small diffs. Tests for every fix. Update README if the public API changes.
