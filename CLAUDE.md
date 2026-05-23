# CLAUDE.md

This file provides guidance for AI assistants working in the `osoobe/laravel-traits` codebase.

## Project Overview

`osoobe/laravel-traits` is a Laravel package providing reusable Eloquent model traits. It is a **pure library** — no migrations, no application models, no routes. Consumers add these traits to their own Eloquent models.

- **Package**: `osoobe/laravel-traits`
- **Namespace root**: `Osoobe\LaravelTraits\`
- **Laravel support**: 6.x – 12.x
- **License**: MIT

## Repository Structure

```
laravel-traits/
├── src/
│   ├── LaravelTraitsServiceProvider.php   # Empty provider (auto-discovered via composer.json)
│   ├── Support/                            # 16 Eloquent model traits
│   │   ├── Active.php
│   │   ├── AdvanceWhereQuery.php
│   │   ├── BelongsToUser.php
│   │   ├── HasEmail.php
│   │   ├── HasFiles.php
│   │   ├── HasFullTextSearch.php
│   │   ├── HasLang.php
│   │   ├── HasLocation.php
│   │   ├── HasPhoneNumber.php
│   │   ├── HasSlug.php
│   │   ├── IsDefault.php
│   │   ├── IsVerified.php
│   │   ├── SEO.php
│   │   ├── TimeDiff.php
│   │   ├── ToString.php
│   │   └── Userstamp.php
│   └── Test/
│       └── TestRouteTrait.php              # HTTP route testing helpers for downstream apps
├── tests/
│   ├── TestCase.php                        # Base Orchestra\Testbench test case (SQLite)
│   └── Support/                            # One test file per trait
│       ├── ActiveTest.php
│       ├── ...
│       └── UserstampTest.php
├── .github/workflows/
│   └── tests.yml                           # CI matrix: Laravel 8–12 × PHP 8.1–8.4
├── composer.json
├── phpunit.xml
└── readme.md
```

## Development Setup

```bash
composer install
composer test          # alias for vendor/bin/phpunit
```

## Architecture & Conventions

### Trait location
All production traits live under `src/Support/`. The `src/Test/` directory contains testing helpers for **downstream packages and applications**, not for testing this package itself.

### Namespace map
```
Osoobe\LaravelTraits\Support\  →  src/Support/
Osoobe\LaravelTraits\Test\     →  src/Test/
Osoobe\LaravelTraits\Tests\    →  tests/       (dev-only)
```

### Trait naming patterns
| Prefix | Meaning |
|--------|---------|
| `Has*` | Adds capabilities/methods (`HasEmail`, `HasFiles`) |
| `Is*` | Adds status/state checks (`IsVerified`, `IsDefault`) |
| `Belongs*` | Adds relationship definitions (`BelongsToUser`) |
| Noun | Domain-specific behavior (`Active`, `TimeDiff`, `SEO`, `Userstamp`) |

### Query scope convention
All Eloquent query scopes follow the standard Laravel `scope{Name}($query, ...)` convention and are accessed as chainable builder calls:
```php
Post::active()->notExpired()->recentlyCreated(7)->get();
```

### Dependency convention
Traits may only depend on:
1. `illuminate/support` — Eloquent, Facades, Str helpers
2. `osoobe/laravel-utilities` — `Utilities`, `Str`, `FormatHelper` helpers
3. `Carbon\Carbon` — date/time operations

Do not add new package dependencies without updating `composer.json`.

### Required database columns
Each trait assumes specific column names in the consumer's migration:

| Trait | Required Columns |
|-------|-----------------|
| `Active` | `is_active` (boolean/tinyint), `hidden` (boolean/tinyint) |
| `HasEmail` | `email` (string), `email_verified_at` (nullable timestamp) |
| `HasFiles` | `id` (auto-increment — used in path generation) |
| `HasLang` | `lang` (string) |
| `HasLocation` | `street_address`, `city`, `state`, `zip_code`, `country` |
| `HasPhoneNumber` | `phone_number` (string) |
| `HasSlug` | `slug` (string, unique) |
| `IsDefault` | `is_default` (boolean/tinyint) |
| `IsVerified` | `verified` (boolean/tinyint) |
| `TimeDiff` | `created_at`, `updated_at`, `expiry_date` (nullable timestamp) |
| `Userstamp` | `creator_id`, `creator_type`, `editor_id`, `editor_type` |

## Trait Reference

### Active
Query scopes for active/inactive/hidden status.
- `scopeActive($query)` — WHERE `is_active` = 1
- `scopeNotActive($query)` — WHERE `is_active` != 1
- `scopeHidden($query)` — WHERE `hidden` = 1

### AdvanceWhereQuery
No-op conditional where clause.
- `scopeWhereKeyOrNull($query, $key, $value)` — adds WHERE only if `$value` is not empty

### BelongsToUser
Hardcoded `belongsTo` to `App\Models\User` via `user_id`. No config override.
- `user()` → `BelongsTo`

### HasEmail
Email verification helpers and scopes.
- `isEmailVerified()` — checks both `email` and `email_verified_at` are non-null
- `scopeEmail($query, $email)` — exact email match
- `scopeEmailVerified($query)` — non-null `email_verified_at`
- `scopeEmailNotVerified($query)` — null `email_verified_at`
- `scopeEmailVerifiedSince($query, $time, $carbon_fn)` — verified within a time window

### HasFiles
File storage helpers. Path format: `{plural-kebab-class}/{kebab-class}-{id}/`
- `kekabPath()` — generates the namespaced storage path from class name + ID
- `storeFile($path, $contents, $disk, $options)` — writes to any disk
- `storePublicFile($path, $contents, $options)` — wraps `storeFile` for `public` disk
- `storePrivateFile($path, $contents, $options)` — wraps `storeFile` for `private` disk
- `getPublicFile($filename)` / `getPrivateFile($filename)` — absolute path to stored file
- `publicDisk()` / `privateDisk()` — returns a Storage disk builder (Laravel 8+) or path array

### HasFullTextSearch
MySQL MATCH/AGAINST full-text search. **Does not work with SQLite.**
- `scopeFullTextSearch($query, $columns, $text, $boolMode)` — adds whereRaw FTS clause
- `buildFullTextSearch($columns, $text, $boolMode)` — static SQL string builder (sanitizes text)
- `selectFTSScore($columns, $text, $boolMode)` — builds relevance score SELECT expression

Requires a MySQL `FULLTEXT` index on the target columns.

### HasLang
Multi-language support via a `lang` column.
- `scopeLang($query)` — matches `app()->getLocale()`
- `scopeLangEN($query)` — `lang = 'en'`
- `language()` — `hasOne('App\Language', 'iso_code', 'lang')` (consumer must have `App\Language`)
- `isLangRTL()` — returns `is_rtl` from the related language record

### HasLocation
Address formatting helpers (no DB queries, pure string operations).
- `isValidLocation()` — all four address fields populated
- `getFullAddress($delimiter, $exclude_zip)` — formatted address string
- `getFullAddressWithZipCode()` — includes zip code
- `getStateAddress()` / `getCityAddress()` — partial address strings
- `getGoogleMapLinkAttribute()` — `http://maps.google.com/?q=<address>`
- `getAddressArray()` — keyed array: `street`, `city`, `state`, `zip`, `country`

### HasPhoneNumber
- `getPhoneNumberBaseAttribute()` — strips `+1` prefix from `phone_number`
- `scopePhoneNumber($query, $phone_number)` — `whereIn` both formatted and raw values

### HasSlug
- `hasSlug($query, $slug)` — query scope: WHERE `slug` = value
- `findBySlugOrFail($slug)` — static finder by slug OR id; throws `ModelNotFoundException`

### IsDefault
- `scopeIsDefault($query)` — WHERE `is_default` = 1
- `scopeNotDefault($query)` — WHERE `is_default` != 1

### IsVerified
- `scopeVerified($query)` — WHERE `verified` = 1
- `scopeNotVerified($query)` — WHERE `verified` != 1
- `getIsVerifiedAttribute()` — returns `(bool) $this->verified`

### SEO
**Abstract trait** — must implement two methods before using.
- **Must implement**: `getRouteURL()`, `getSEOTitleAttribute()`
- `getSEODescriptionAttribute()` — auto-generates from class name + `config('app.name')`
- `getURLAttribute()` — delegates to `getRouteURL()`

### TimeDiff
The most comprehensive trait (~40 methods). Covers time accessors, expiry management, and time-based scopes.

**Accessors**: `created_time_diff`, `updated_time_diff`, `posted_time_diff`, `expiry_date_time_diff`

**Expiry**: `isExpired()`, `expireInDays(int)`, `expireInHours(int)`, `expiresInMinutes()`, `expiresInSeconds()`

**Scopes** (partial list): `expired`, `notExpired`, `createdToday`, `createdSinceWeek`, `recentlyCreated`, `recentlyUpdated`, `createdDate`, `createdWeek`, `createdMonth`, `olderThan`, `betweenDates`, `hourly`, `daily`, `weekly`, `monthly`, `yearly`, `createdHoursAgo`, `createdMinutesAgo`, `updatedSinceWeek`

**Instance checks**: `recentlyCreated(int, string)`, `recentlyUpdated(int, string)`, `justCreated(int)`

### ToString
Converts selected model property values to a formatted string.
- `toString($delimiter, $format, ...$properties)` — alias for `propertyToString`
- `propertyToString($delimiter, $format, ...$properties)` — iterates properties/accessor methods

### Userstamp
Automatically fills `creator_id/type` on create and `editor_id/type` on create+update using `Auth::user()`.
- `creator()` — `morphTo` relationship
- `editor()` — `morphTo` relationship
- Boot method fires on `creating` and `updating` events

## Testing

Tests use [Orchestra Testbench](https://github.com/orchestral/testbench) with an in-memory SQLite database.

### Test anatomy
Each test file in `tests/Support/`:
1. Defines an inline `{Trait}TestModel` class that uses the trait
2. Creates a schema table in `setUp()` using `Schema::create()`
3. Drops the table in `tearDown()` (or uses `RefreshDatabase`)
4. Tests every public method — happy path and edge cases

### Traits requiring special test setup
| Trait | Note |
|-------|------|
| `HasFullTextSearch` | Test `buildFullTextSearch`/`selectFTSScore` as pure string ops; skip `scopeFullTextSearch` on SQLite |
| `HasLang` | `language()` and `isLangRTL()` require `App\Language`; test scopes only |
| `BelongsToUser` | Relationship hardcoded to `App\Models\User`; test relation type |
| `HasFiles` | Use `Storage::fake('public')` / `Storage::fake('private')` |
| `Userstamp` | Use `$this->actingAs($user)` or `Auth::shouldReceive` |

## Known Issues / Quirks

- `scopeYearly($query, $months=0)` — parameter is named `$months` but represents years. Do not rename (breaking change).
- `publicDisk()` and `privateDisk()` both resolve to `app/private/` storage. `publicDisk` appears to be a copy-paste oversight.
- `scopeExpired` / `scopeNotExpired` check `isset($this->expiry_date)` on a fresh model instance; when called statically (`Model::expired()`), `$this` is a fresh instance with no attributes and the scope silently passes through. Add `expiry_date` to `$casts` in your model but ensure you also hydrate the attribute for the scope guard to engage.
- `HasLang` assumes an `App\Language` model exists in the consumer's application.
- `BelongsToUser` is hardcoded to `App\Models\User` with no config override.
- `SEO` trait has two abstract methods — any model using it must implement both.
- `scopeTimeFrequencey` contains a deliberate typo in the method name; use `timeFrequency` (the alias) in query calls.

## Adding a New Trait

1. Create `src/Support/MyNewTrait.php` in namespace `Osoobe\LaravelTraits\Support`
2. Use `trait MyNewTrait { ... }` (not a class or interface)
3. Add a corresponding `tests/Support/MyNewTraitTest.php`
4. Document required DB columns in the trait docblock
5. Update `readme.md` with the new trait's section
6. List it in the traits table in both `readme.md` and `CLAUDE.md`
