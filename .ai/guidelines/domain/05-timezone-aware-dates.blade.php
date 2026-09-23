@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp

# Timezone-Aware Dates — Display & Usage

**Priority: HIGH** — Reading and displaying date/time values must preserve timezone context. The migration-column rules (`--module` flag and the `Tz` column variants) moved to the `migrations` project rule (`.ai/rules/migrations.md`), loaded when a migration file is edited. This guideline covers display, queries, Carbon, and the database session.

## Context

This project uses PostgreSQL with `APP_TIMEZONE=UTC` and `display_timezone=America/Sao_Paulo`. The `timestamptz` type stores absolute UTC timestamps, allowing PostgreSQL to handle timezone conversion correctly. The non-tz `timestamp` type stores naive datetimes that lose timezone context and cause ±3h display bugs.

## Display timezone

When displaying dates to users, always use `config('app.display_timezone')`:

@verbatim
<code-snippet name="Display timezone conversion" lang="php">
// In Blade/Livewire:
$date->timezone(config('app.display_timezone'))->format('d/m/Y H:i')

// In Filament table columns:
TextColumn::make('created_at')
    ->dateTime('d/m/Y H:i')
    ->timezone(config('app.display_timezone'))
</code-snippet>
@endverbatim

## In raw SQL queries

When converting timestamps for display in raw SQL, use `AT TIME ZONE` with the display timezone:

@verbatim
<code-snippet name="SQL timezone conversion" lang="sql">
-- Convert timestamptz to display timezone:
SELECT occurred_at AT TIME ZONE 'America/Sao_Paulo' AS local_time
FROM events;

-- NEVER use double AT TIME ZONE (causes +3h shift):
-- BAD:  occurred_at AT TIME ZONE 'UTC' AT TIME ZONE 'America/Sao_Paulo'
-- GOOD: occurred_at AT TIME ZONE 'America/Sao_Paulo'
</code-snippet>
@endverbatim

## Carbon usage

@verbatim
<code-snippet name="UTC timestamp creation" lang="php">
// Correct — uses app timezone (UTC):
now()
Carbon::now()

// For explicit UTC:
now()->utc()

// NEVER hardcode timezone in application logic:
// BAD:  now()->timezone('America/Sao_Paulo')
// GOOD: now()  (app is UTC, display converts later)
</code-snippet>
@endverbatim

## PostgreSQL session timezone

Do NOT set `'timezone' => 'UTC'` in `config/database.php` pgsql connection. This causes double-conversion on `timestamptz` columns. Let PostgreSQL use its server default.

## What triggers this guideline

- Display code showing dates — use `config('app.display_timezone')`
- Raw SQL with timestamp conversion — use single `AT TIME ZONE`
- Carbon in application logic — use `now()` (UTC), never hardcode a timezone
- `config/database.php` pgsql connection — never set `'timezone' => 'UTC'`

## Verification

Before marking a date/time task as done, confirm:

1. Display code uses `config('app.display_timezone')` for user-facing dates
2. Raw SQL queries use single `AT TIME ZONE` (never double)
3. No hardcoded `timezone('America/Sao_Paulo')` in application logic
4. `config/database.php` pgsql connection does not set `'timezone' => 'UTC'`
