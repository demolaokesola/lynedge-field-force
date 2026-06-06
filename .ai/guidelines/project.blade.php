{{-- Pharma Sales & Demand-Creation Tracker — project guidelines (always loaded by Boost) --}}
{{-- This file is self-contained. Phase prompts may say "per the guidelines" or "§N"; --}}
{{-- the authoritative rules are HERE. Full schema/worked examples live in docs/playbook.md. --}}

## What this app is
A field-force tracker for a Nigerian pharma company: Sales Reps' call activities,
product distribution (invoices), and customer deposits, against a
Region -> Territory -> Position(team) org tree, with target attainment.

## Domain invariants (non-negotiable)
- A Position is (territory, team). A position's team.kind MUST equal the territory's
  team_policy (strict|liberal). Strict territories also enforce UNIQUE(territory,team)
  over active positions. NEVER model strict/liberal as separate entities.
- Products and teams are many-to-many (product_team). Teams are typed strict|liberal.
  A product may belong to AT MOST ONE strict team, and any number of liberal teams.
  Enforce on pivot-attach. This is what keeps the strict partition valid.
- A distribution is created under ONE position and is single-team (team_id copied
  from the position). Each line's product MUST belong to that position's team's
  product set (RepScope::productsForPosition). This is the "can't sell the same
  product" guard.
- Targets are ANNUAL volumes per product, set at cycle start via tiers. A mid-cycle
  change is a PRORATED BLEND. Materialise into rep_monthly_targets:
  monthly target = annual_of_active_segment * (1/12). The divisor is ALWAYS 1/12
  (full year), NEVER the span length. Re-materialise on any assignment change.
- Transactions denormalise territory_id/team_id at write time — intentional (reorg
  history). Do not "fix" by joining live.

## Roles
superuser, platform_admin, hq_lead, regional_head, sales_rep, supervisor, accountant.
- A rep's org anchor is their POSITION (-> territory -> region), not an explicit
  region_id. users.region_id is set ONLY for regional_head (and optionally accountant).
- supervisor = a sales_rep + region-read. They still hold a position; their region is
  derived from that position.

## Two scopes — keep them separate
There are TWO visibility scopes. Do not conflate them; they key off different columns.

### A) Transaction scope: scopeVisibleTo($q, $user) on Call, Distribution, Deposit
These models have user_id and territory_id.
- superuser | platform_admin | hq_lead -> all
- accountant -> all (deposits; adjustable to regional later)
- regional_head -> whereIn('territory_id', territories of their region_id)
- supervisor -> whereIn('territory_id', territories of their region) [region derived
  from their open position] -- this is READ visibility; WRITE stays own via policy
- sales_rep -> where('user_id', $user->id)  (own activity only)

### B) Org scope: scopeVisibleOrgTo($q, $user) on Region, Territory
These models have NO user_id; Territory has region_id, Region is keyed by id.
- superuser | platform_admin | hq_lead | accountant -> all
- regional_head -> Territory: where('region_id', $user->region_id);
                   Region:    whereKey($user->region_id)
- sales_rep | supervisor -> whereRaw('1 = 0')  (empty) FOR NOW.
  Their org anchor is the Position (added in Phase 2). When positions exist, replace
  this branch with: regions of the user's active positions' territories.
- NEVER use "null region_id means see all" — a rep has a null region_id and would
  wrongly see everything.

### Admin/master-data resources
Region, Territory, Team, Users resources are admin-only (policy-gate canViewAny to
platform_admin|superuser). Admins manage the whole org and see ALL rows, so apply NO
row scope on these resources. scopeVisibleOrgTo is for when NON-admins read territory
data (management dashboards, field selects), not for the admin resources.

## Panels
Three Filament panels. A panel is navigation/UX only — NEVER treat it as
authorization. Row/action access is Policies + the scopes above, independent of panel.
Resource classes may be shared across panels.
- field      -> sales_rep, supervisor      (mobile-first: own activity + my attainment)
- office     -> platform_admin, accountant  (config/master-data/users; deposits & recon)
- management -> hq_lead, regional_head      (oversight: dashboards, scoped read-only)
- superuser  -> all panels (break-glass)
Gate entry with User::canAccessPanel($panel). Within office, policies tailor nav so the
accountant sees only finance and platform_admin sees config/reports.

## Stack conventions
- PostgreSQL only. Partial unique indexes via DB::statement in migrations.
- Money: decimal(18,2) Naira, Money cast. Quantities: decimal(14,2).
- PHP enums for every status/type/kind column; back with Filament enum support.
- Tests: Pest. Every model gets a factory; every business rule gets a feature test.
- Filament is v5 (== v4 API on Livewire 4). When unsure of ANY Filament/Laravel
  signature, call the search-docs MCP tool — do not guess.
- RBAC: spatie/laravel-permission via Shield.

## Security
- Never expose env values, secrets, or stack traces in UI or commits.
- Never mass-assign user_id, status, amount, or team_id from the client.