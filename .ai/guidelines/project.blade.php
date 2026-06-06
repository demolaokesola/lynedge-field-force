{{-- Pharma Sales & Demand-Creation Tracker — project guidelines --}}

## What this app is
A field-force tracker for a Nigerian pharma company: Sales Reps' call activities,
product distribution (invoices), and customer deposits, against a
Region -> Territory -> Position(team) org tree, with target attainment.

## Non-negotiable domain rules
- A Position is (territory, team). team.kind MUST equal territory.team_policy
  (strict|liberal). Strict territories also enforce UNIQUE(territory,team) over
  active positions. NEVER model strict/liberal as separate entities.
- Products and teams are many-to-many (product_team). Teams are typed strict|liberal.
  A product may belong to AT MOST ONE strict team, and any number of liberal teams.
  Enforce on pivot-attach. This is what keeps the strict partition valid.
- A distribution is created under one position and is single-team (team_id copied
  from the position). Each line's product MUST belong to that position's team's
  product set (RepScope::productsForPosition). This is the "can't sell the same
  product" guard.
- Targets are ANNUAL volumes per product, set at cycle start via tiers. A mid-cycle
  change is a PRORATED BLEND. Materialise into rep_monthly_targets:
  monthly target = annual_of_active_segment * (1/12). The divisor is ALWAYS 1/12
  (full year), NEVER the span length. Re-materialise on any assignment change.
- Transactions denormalise territory_id/team_id at write time — intentional (reorg
  history). Do not "fix" by joining live.

## Panels
- Three panels: field (sales_rep, supervisor), office (platform_admin, accountant),
  management (hq_lead, regional_head); superuser accesses all. Gate entry with
  User::canAccessPanel().
- A panel is navigation/UX only. NEVER treat it as authorization. Row/action access
  is Policies + scopeVisibleTo(), independent of panel. Resource classes may be
  shared across panels.

## Stack conventions
- PostgreSQL only. Partial unique indexes via DB::statement in migrations.
- Money: decimal(18,2) Naira, Money cast. Quantities: decimal(14,2).
- PHP enums for every status/type/kind column; back with Filament enum support.
- Tests: Pest (NOT PHPUnit — this overrides the bundled phpunit guideline). Every model
  gets a factory; every business rule gets a feature test. Feature tests extend Tests\TestCase
  with RefreshDatabase against the Postgres `testing` connection (pinned in phpunit.xml).
  pest-plugin-livewire IS installed — use `livewire(...)` for Filament/Livewire component
  tests. pest-plugin-laravel is intentionally NOT installed (no Laravel 13 support yet), so
  its global helpers are unavailable: use `$this->get()`, `$this->actingAs()`,
  `$this->assertDatabaseHas()` (method forms), not the bare `actingAs()`/`assertDatabaseHas()`
  functions.
- Filament is v5 (== v4 API on Livewire 4). When unsure of ANY Filament/Laravel
  signature, call search-docs — do not guess.
- RBAC: spatie/laravel-permission via Shield. Visibility via scopeVisibleTo();
  write access via Policies. Keep them separate.

## Security
- Never expose env values, secrets, or stack traces in UI or commits.
- Never mass-assign user_id, status, amount, or team_id from the client.