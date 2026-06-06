# Pharma Sales & Demand-Creation Tracker — Build Playbook
**Stack:** Laravel 13 · Filament 5 (Livewire 4 / Tailwind 4) · PostgreSQL · Pest · Laravel Sail · Laravel Boost
**Context:** Rebuild of an Oracle APEX field-force tracker for a Nigerian pharma company.

> Filament 5 is **functionally identical to Filament 4** — same forms, tables, resources, panels, relation managers. The only reason v5 exists is Livewire 4 support. So write v4-idiom Filament, pin the v5-compatible release of every plugin, and let Boost's `search-docs` confirm exact signatures.

### Decisions locked in
- **Products ↔ teams are many-to-many.** Teams are typed `strict | liberal`. A product joins **at most one strict team** and **any number of liberal teams** — this keeps strict teams a disjoint partition, so the strict guarantee stays a database-level fact.
- **Targets are set as an annual volume per product at cycle start** (via tier). Monthly is derived for pacing only. A **mid-cycle change is a prorated blend** of the spans.
- **Three Filament panels** — `field` (reps/supervisors), `office` (admin + accountant), `management` (HQ/regional oversight) — sharing resource classes. Panels group navigation by audience; authorization stays in Policies + `scopeVisibleTo()`.

---

## 0. Assumptions & key decisions

Override before you start if any are wrong.

1. **Org tree is fixed at 3 levels** under an implicit HQ: `Region → Territory`. HQ is conceptual (the HQ Lead role has global scope), so no `hq` table. If a Regional Head can ever own more than one region, swap `users.region_id` for a pivot.
2. **Products ↔ teams (product groups) are many-to-many** via `product_team`. Teams carry a `kind` (`strict | liberal`). **A product may belong to at most one strict team, and to any number of liberal teams.** This makes strict teams a disjoint partition of the catalog, which keeps "no two reps sell the same product" true inside a strict territory without per-product checks. *Assumes a single strict slicing of the catalog org-wide* (your Team A / Team B partition). If different regions ever need different, overlapping strict groupings, relax this to territory-scoped product-disjointness (§2.6).
3. **The atomic manned unit is a Position** = `(territory, team)`. Strict vs liberal is a *constraint on positions within a territory*, plus the team-kind match — not two different entities.
4. **Targets are a time series of annual figures.** A tier carries an **annual** volume per product; reps get time-bounded assignments; a mid-cycle change is a **prorated blend** of the spans; YTD is computed against a materialised monthly table.
5. **Transactions denormalise `territory_id` / `team_id` at write time.** Reorgs happen; reporting must reflect historical truth, so the slot/team is frozen onto each call, distribution, and deposit.
6. **Money:** `decimal(18,2)` Naira, cast to a Money value object (or integer kobo). **Quantities:** `decimal(14,2)` to allow part-packs.
7. **RBAC:** spatie/laravel-permission via Filament Shield (v5-compatible release). If Shield lags on v5, fall back to plain spatie + hand-written Policies.
8. **Three panels by audience** (see §5 for the principle):
   - **`field`** — Sales Rep, Supervisor. Mobile-first. Daily activity + personal performance.
   - **`office`** — Platform Admin, Superuser, Accountant. Back office: master-data/config, users & roles, and deposits/reconciliation.
   - **`management`** — HQ Lead, Regional Head. Oversight only: dashboards, leaderboards, scoped reports, read-only drill-downs.
   A panel is a navigation/UX context, **not** the security boundary — authorization stays in Policies + `scopeVisibleTo()`, so resource classes are safely shared across panels.

---

## 1. Data model

Grouped by domain. Only load-bearing columns are listed; add timestamps/soft-deletes per your conventions.

### Org & access
- **regions** — `id, name, code`
- **territories** — `id, region_id, name, code, team_policy enum('strict','liberal') default 'strict'`
- **teams** *(product groups: A, B, …)* — `id, name, code, kind enum('strict','liberal'), active`
- **users** — standard + `region_id (nullable)`, `is_active`. Roles via Shield. Reps derive region through position → territory → region; `region_id` is set explicitly only for region-scoped non-reps (Regional Head, optionally Accountant).
- **positions** — `id, territory_id, team_id, code, label, enforce_team_uniqueness bool, status enum('active','frozen') default 'active'`
  - The position's `team.kind` must equal the `territory.team_policy` (enforced — §2.3).
  - Partial unique: `UNIQUE(territory_id, team_id) WHERE enforce_team_uniqueness AND status='active'`
- **position_assignments** *(temporal occupancy)* — `id, position_id, user_id, effective_from date, effective_to date null, status enum('active','ended'), notes`
  - Partial unique: `UNIQUE(position_id) WHERE effective_to IS NULL` (one open occupant per position)

### Master data
- **products** — `id, name, sku, pack_size, unit_price (nullable), active` *(no team_id — membership is the pivot below)*
- **product_team** *(pivot, many-to-many)* — `product_id, team_id` (PK on both). Invariant: a product may be linked to **≤1 strict team** and any number of liberal teams (§2.0).
- **customers** *(buying customers, tied to territory)* — `id, territory_id, name, type, address, phone`
- **demand_creator_types** *(lookup)* — `id, name` — seed: Hospital Pharmacist, Prescribing Community Pharmacist, Doctor/CHEW, Laboratory Scientist, Public Mobilization Place, Merchandizer
- **demand_creators** — `id, demand_creator_type_id, territory_id, name, affiliation, phone, address`

### Transactions
- **calls** — `id, user_id, position_id, territory_id, demand_creator_id, call_type enum('physical_visit','phone_call'), called_at datetime, latitude null, longitude null, notes`
- **call_product** *(pivot, products detailed on the call)* — `call_id, product_id`
- **distributions** *(invoices — created under one position, so single-team)* — `id, user_id, position_id, territory_id, team_id, customer_id, invoice_number, invoice_date date, total_amount decimal(18,2), status enum('draft','posted','void'), notes`
  - `team_id` is denormalised from the chosen position's team. All lines are products in that team.
- **distribution_lines** — `id, distribution_id, product_id, quantity decimal(14,2), unit_price decimal(18,2), line_amount decimal(18,2)` *(team is the distribution header's; no per-line team_id)*
- **deposits** *(bulk payment, not invoice-bound)* — `id, customer_id, territory_id, received_by_user_id, amount decimal(18,2), deposit_date date, reference, bank, channel, status enum('unreconciled','partially_reconciled','reconciled','disputed'), notes`
- **deposit_allocations** *(optional reconciliation)* — `id, deposit_id, distribution_id null, amount decimal(18,2), allocated_by_user_id, allocated_at`

### Targets
- **cycles** — `id, name, starts_on date, ends_on date, is_current bool` (e.g. "2025/2026", 2025-02-01 → 2026-01-31)
- **target_tiers** — `id, name, description, active`
- **target_tier_lines** — `id, target_tier_id, product_id, annual_volume decimal(14,2)` *(annual, set at cycle start)*
- **target_assignments** — `id, cycle_id, user_id, position_id null, target_tier_id null, basis enum('tier','custom'), effective_from date, effective_to date null, reason enum('initial','tier_change','maternity','leave','adjustment','custom'), notes`
- **target_assignment_lines** *(overrides; used when basis='custom' or to tweak specific products)* — `id, target_assignment_id, product_id, annual_volume decimal(14,2)`
- **rep_monthly_targets** *(materialised — derived from assignments)* — `id, cycle_id, user_id, year_month date, product_id, target_qty decimal(14,2)` — `UNIQUE(cycle_id, user_id, year_month, product_id)`

---

## 2. Teams: typed, many-to-many, strict vs liberal

### 2.0 Team typing + the partition-preserving rule
A team is built for strict **or** liberal selling (`teams.kind`). A product can be in several teams, but **at most one strict team**. Because strict teams therefore never share a product, they form a disjoint partition of the catalog — which is what makes "no product sold by two reps" hold inside a strict territory.

Enforce on pivot-attach (a `FormRequest`/Filament rule, or a `pivotAttaching` hook):
```php
if ($team->kind === TeamKind::Strict) {
    $clash = $product->teams()
        ->where('kind', TeamKind::Strict)
        ->whereKeyNot($team->id)
        ->exists();
    if ($clash) {
        $fail('A product can belong to at most one strict team. Remove it from the other strict team first, or make this team liberal.');
    }
}
```

### 2.1 Migration (partial unique indexes)
```php
// positions migration
DB::statement("
    CREATE UNIQUE INDEX positions_strict_team_unique
    ON positions (territory_id, team_id)
    WHERE enforce_team_uniqueness AND status = 'active'
");

// position_assignments migration
DB::statement("
    CREATE UNIQUE INDEX one_open_assignment_per_position
    ON position_assignments (position_id)
    WHERE effective_to IS NULL
");
```
`UNIQUE(territory_id, team_id)` is **sufficient** for the strict guarantee now, precisely because §2.0 keeps strict teams disjoint.

### 2.2 Keep the uniqueness flag in sync
```php
// PositionObserver
public function saving(Position $position): void
{
    $position->enforce_team_uniqueness =
        $position->territory->team_policy === TeamPolicy::Strict;
}

// TerritoryObserver — re-sync children when policy flips (rare)
public function updated(Territory $territory): void
{
    if ($territory->wasChanged('team_policy')) {
        $territory->positions()->update([
            'enforce_team_uniqueness' => $territory->team_policy === TeamPolicy::Strict,
        ]);
    }
}
```

### 2.3 Validation: kind-match + strict-uniqueness
```php
// (a) team.kind must match territory.team_policy
$team = Team::find($teamId);
$territory = Territory::find($this->territoryId);
if ($team && $territory && $team->kind->value !== $territory->team_policy->value) {
    $fail("A {$territory->team_policy->value} territory can only hold {$territory->team_policy->value} teams.");
}

// (b) one active position per (territory, team) in STRICT territories
if ($territory?->team_policy === TeamPolicy::Strict) {
    $clash = Position::where('territory_id', $this->territoryId)
        ->where('team_id', $teamId)->where('status', 'active')
        ->when($this->ignoreId, fn ($q) => $q->whereKeyNot($this->ignoreId))
        ->exists();
    if ($clash) $fail('This team is already manned in this (strict) territory.');
}
```
Filament team picker, filtered by the territory's policy:
```php
Select::make('team_id')
    ->options(fn (Get $get) => Team::query()
        ->where('kind', Territory::find($get('territory_id'))?->team_policy)
        ->where('active', true)->pluck('name', 'id'))
    ->required();
// (confirm exact Get/relationship syntax for Filament 5 via Boost search-docs)
```

### 2.4 Transaction guard — "can't distribute the same product"
A distribution is created **under one position** (the rep picks which of their active positions in the territory they're invoicing under). That position fixes the team, and every line's product must belong to that team's product set.
```php
// App\Services\RepScope
public function invoiceablePositions(User $rep, int $territoryId, ?Carbon $on = null): Collection
{
    $on ??= now();
    return Position::query()
        ->where('territory_id', $territoryId)->where('status', 'active')
        ->whereHas('assignments', fn ($q) => $q
            ->where('user_id', $rep->id)
            ->where('effective_from', '<=', $on)
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $on)))
        ->get();
}

public function productsForPosition(Position $position): Collection
{
    return $position->team->products()->pluck('products.id'); // via product_team
}
```
Line validation: reject any `distribution_line.product_id` not in `productsForPosition($distribution->position)`.

### 2.5 Coverage & vacancy (report queries)
- **Vacant positions:** `positions` with no `position_assignments` where `effective_to IS NULL`.
- **Strict coverage gaps:** for each strict territory, active strict `teams` minus the `team_id`s with an active, occupied position.

### 2.6 Fallback if you ever need multiple strict slicings
Drop the global ≤1-strict-team rule and enforce **territory-scoped product-disjointness**: when adding a position to a strict territory, reject if any of that team's products are already covered by another active position's team in the same territory. Optionally back it with a materialised `(territory_id, product_id)` unique index (rebuilt on position *and* product_team changes). Not needed for the current A/B partition.

---

## 3. Targets — annual figures, prorated, materialised monthly

### 3.1 The model in one line
A tier (or custom override) sets an **annual** volume per product. A rep's effective target over the cycle is a **prorated blend** of whatever assignment was in effect each month, materialised into `rep_monthly_targets` so YTD is a `SUM`.

### 3.2 Materialisation rule (mind the divisor)
```
For each month M in [cycle.starts_on .. cycle.ends_on]:
  seg    = the target_assignment active on the 1st of M
  annual = per-product annual_volume for seg
             basis=custom -> target_assignment_lines
             basis=tier   -> target_tier_lines, overridden per-product by assignment_lines
  weight = seasonal weight for M (default 1/12 — even split)
  target_qty[M, product] = annual * weight
  upsert one rep_monthly_targets row per (rep, cycle, M, product)
```
**Critical:** the divisor is always the *full year* (`1/12`), never the number of months the span covers. Dividing the annual by the span length is the bug that silently breaks proration. Re-run on any assignment create/update/delete (queue from a `TargetAssignmentObserver`).

### 3.3 Attainment
```php
$targetYtd = RepMonthlyTarget::where('cycle_id', $cycle->id)
    ->where('user_id', $rep->id)
    ->where('year_month', '<=', $asOf->copy()->startOfMonth())
    ->where('product_id', $product->id)->sum('target_qty');

$actualYtd = DistributionLine::whereHas('distribution', fn ($q) => $q
        ->where('user_id', $rep->id)->where('status', 'posted')
        ->whereBetween('invoice_date', [$cycle->starts_on, $asOf]))
    ->where('product_id', $product->id)->sum('quantity');

$attainmentPct = $targetYtd > 0 ? round($actualYtd / $targetYtd * 100, 1) : null;
```
Full-cycle attainment uses the same query without the `<=` month cap on targets.

### 3.4 Worked examples (Cycle 2025/2026, Feb–Jan, Product X)
Baseline **Tier 2 = 1,200/yr** (even split ⇒ 100/month).

**Maternity (target 0 for May–Jul):** custom assignment, annual 0 May 1–Jul 31; Tier 2 either side.
- Effective annual = 1,200 × 9/12 + 0 = **900**; monthly: Feb–Apr 100, May–Jul 0, Aug–Jan 100.
- YTD end-of-Aug = 300 + 0 + 100 = **400**. Early months keep their pace; leave months are zero.

**Mid-year tier change (1,200 → 1,500 effective Aug):**
- Effective annual = 1,200 × 6/12 + 1,500 × 6/12 = **1,350**. Pre-Aug months stay at 100/mo; the bar for elapsed months is never moved retroactively.

### 3.5 Optional: seasonality
Instead of `1/12` evenly, attach a 12-month weight curve (per product or per tier, summing to 1) so the annual spreads to match real demand. Default to even split unless you have the curve.

---

## 4. Data scoping by role

Roles: **Superuser, Platform Admin, HQ Lead, Regional Head, Sales Rep, Supervisor, Accountant.**

One query scope, applied in every transaction Resource's `getEloquentQuery()` plus matching Policies.
```php
// App\Models\Concerns\ScopesToViewer  (trait on Call, Distribution, Deposit, ...)
public function scopeVisibleTo(Builder $q, User $user): Builder
{
    if ($user->hasAnyRole(['superuser', 'platform_admin', 'hq_lead'])) return $q;       // all
    if ($user->hasRole('accountant')) return $q;                                         // all deposits (or scope by region if required)

    if ($user->hasRole('regional_head')) {
        return $q->whereIn('territory_id', Territory::where('region_id', $user->region_id)->select('id'));
    }
    if ($user->hasRole('supervisor')) {                                                  // rep + region read
        $regionId = $user->currentRegionId();   // derived from their open position
        return $q->whereIn('territory_id', Territory::where('region_id', $regionId)->select('id'));
    }
    return $q->where('user_id', $user->id);                                              // plain rep: own activity
}
```
- **Supervisor = Sales Rep + region-read.** Layer `supervisor` on a normal rep (who still holds a position). Write scope stays self; read scope widens to the region.
- Use **Policies** for write access, `visibleTo()` for visibility. Keep them separate.
- **Panels are orthogonal to this.** `canAccessPanel()` gates *which panel a user can load*; the scope + policies gate *which rows and actions* — independent of panel. A resource shared across `field` and `management` stays correctly scoped in both.

---

## 5. Panels & Laravel Boost

### 5.1 Panel architecture
A Filament panel is a navigation/UX context (its own URL prefix, nav, theme, and a `canAccessPanel()` entry gate). It is **not** the security boundary — that's Policies + `scopeVisibleTo()`. So you register the same resource class in every panel that needs it, and scoping/policies make it behave correctly per user.

- **`field`** (Sales Rep, Supervisor) — mobile-first. Calls, Distributions, Deposits (record only), and a "my attainment" dashboard. Supervisors get region-read widgets via scope, in the same panel.
- **`office`** (Platform Admin, Superuser, Accountant) — back office. Config/master-data + users & roles (Platform Admin/Superuser); deposits, allocations & reconciliation (Accountant); admin reports. Nav is role-tailored within the panel via policies.
- **`management`** (HQ Lead, Regional Head) — oversight only. Leaderboards, coverage/vacancy, scoped reports, read-only drill-downs into calls/distributions/deposits. No config, no data entry.

`canAccessPanel` mapping (on the User model): `field` → sales_rep|supervisor; `office` → platform_admin|accountant; `management` → hq_lead|regional_head; `superuser` → all panels (break-glass).

Shared-resource examples: Distribution registered in `field` (write own) + `management` (read-only); Deposit in `field` (record) + `office` (manage/allocate); report pages/dashboards in `office` (admin, all) + `management` (scoped).

### 5.2 Boost install (Sail)
Boost is a dev-only MCP server (15+ tools: app/DB introspection, Tinker, browser logs, and a `search-docs` tool over version-matched Laravel-ecosystem docs). Its value is that the agent fetches *version-correct* syntax instead of guessing.
```bash
./vendor/bin/sail composer require laravel/boost --dev
./vendor/bin/sail artisan boost:install        # detects packages, writes guidelines + MCP config
./vendor/bin/sail artisan boost:update          # after adding/upgrading packages
```

### 5.3 WSL2 + Sail gotcha
The MCP server is started by your editor running `php artisan boost:mcp`. Under Sail, PHP lives in the container. Either install a matching PHP on the WSL host so the MCP `command` can run `php artisan boost:mcp`, or point the MCP `command` at `docker` with args like `compose exec -T laravel.test php artisan boost:mcp` (container must be up). If Boost's tools "aren't being used," it's almost always this.

### 5.4 Git hygiene
`boost:install` regenerates `.mcp.json`, `CLAUDE.md`, `AGENTS.md`, `junie/` — gitignore those. **Commit** your hand-written `.ai/guidelines/*` files.

### 5.5 Project guidelines — `.ai/guidelines/project.blade.php`
```blade
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
- Tests: Pest. Every model gets a factory; every business rule gets a feature test.
- Filament is v5 (== v4 API on Livewire 4). When unsure of ANY Filament/Laravel
  signature, call search-docs — do not guess.
- RBAC: spatie/laravel-permission via Shield. Visibility via scopeVisibleTo();
  write access via Policies. Keep them separate.

## Security
- Never expose env values, secrets, or stack traces in UI or commits.
- Never mass-assign user_id, status, amount, or team_id from the client.
```

### 5.6 Workflow guideline — `.ai/guidelines/workflow.blade.php`
This is what makes each phase a self-contained, hand-cranked instruction: the agent inspects first and stops after, every time, without you repeating it.
```blade
## How to run a build phase
At the START, before writing code:
1. Read .ai/guidelines/*.
2. Use Boost database-schema and list-models to see current state — do NOT assume
   tables/models exist, inspect them.
3. Run `php artisan test` and confirm green before changing anything.
4. Restate in one line the slice you're about to build, the files you'll touch, and
   which panel(s) the resources register in.

While building:
- Use Boost search-docs for any Filament 5 / Laravel 13 signature. Never guess.
- Migration, then models/enums, then Filament resources (in the named panel[s]),
  then the named Pest tests.

At the END:
1. Run `php artisan test`; all named tests must pass.
2. Summarize the diff (files added/changed) and show test output.
3. STOP. Do not begin the next phase. Wait for me to review and say go.
```
Optional per-turn preamble if you want it spelled out each time: *"Continue the Pharma Tracker build. Phases 0–N are done and committed. Follow .ai/guidelines/workflow.blade.php. Today's slice: ⟨paste phase⟩."*

### 5.7 Optional: Filament Blueprint
Filament shipped **Blueprint** (premium) specifically to make AI agents produce better Filament implementation plans (correct component usage, no vague layouts). On-point if you'll lean heavily on Claude Code for Filament UI.

---

## 6. Phased build prompts

Each fenced block is **one complete instruction you paste into Claude Code as a single turn.** Send them one at a time; the `stop and confirm` is your review gate — the agent builds the slice, stops, you review/test/commit, then paste the next. Boost is installed and `.ai/guidelines/*` (including `workflow.blade.php`) are in place. Standing instruction: *use Boost `search-docs` for exact Filament 5 / Laravel 13 syntax, and `database-schema` before any migration.*

> You install Laravel + Filament + Sail yourself first; Phase 0 picks up from a fresh, running skeleton.

### Phase 0 — Foundation, panels & conventions
```
We are building a Nigerian pharma field-force tracker on Laravel 13 + Filament 5
(Livewire 4 / Tailwind 4) + PostgreSQL, tested with Pest, run via Sail. Read
.ai/guidelines/* before doing anything and use Boost's search-docs for exact syntax.

Foundation only — no domain tables yet:
1. Install & configure spatie/laravel-permission + Filament Shield (v5-compatible).
2. Create THREE Filament panels: field, office, management. Implement
   User::canAccessPanel(Panel $panel): field -> sales_rep|supervisor;
   office -> platform_admin|accountant; management -> hq_lead|regional_head;
   superuser -> all. Make the field panel mobile-first/compact.
3. Add Pest + pest-plugin-laravel; configure a Postgres testing connection.
4. App-wide conventions: a Money cast, a base enum trait/helper for Filament
   labels+colors, an empty ScopesToViewer trait stub.
5. Bind Position/Territory/Target observers in a service provider (stubs).
6. Add spatie/laravel-activitylog but don't log anything yet.

Do NOT create domain models. Write smoke tests that each panel loads and that
canAccessPanel gating works per role. Stop and show me the package list, the three
panels' config, the gating, and test output.
```

### Phase 1 — Org hierarchy, teams (typed) & RBAC
```
Build the org skeleton and roles. Panel: office (admin).

Schema: regions; territories (team_policy enum strict|liberal default strict);
teams (kind enum strict|liberal, code, active). Add region_id + is_active to users.

Roles (Shield): superuser, platform_admin, hq_lead, regional_head, sales_rep,
supervisor, accountant. Seed sensible permission sets; Superuser bypasses via
Gate::before.

Filament (register in the office panel, policy-gated to platform_admin/superuser):
Region, Territory, Team, Users resources. Territory form exposes team_policy with a
helper note; Team form exposes kind. Users resource assigns roles and, for
regional_head, a region.

Implement ScopesToViewer::scopeVisibleTo() per §4 and unit-test each tier. Factories
+ Pest tests for every model and role gating. Stop and show me the resources, scope, tests.
```

### Phase 2 — Positions & assignments (the strict/liberal core)
```
Architectural core — read §2. Panel: office (admin).

Schema: positions (territory_id, team_id, code, label, enforce_team_uniqueness,
status); position_assignments (position_id, user_id, effective_from, effective_to
nullable, status, notes).

Constraints via DB::statement:
- partial unique positions(territory_id, team_id) WHERE enforce_team_uniqueness AND status='active'
- partial unique position_assignments(position_id) WHERE effective_to IS NULL

PositionObserver sets enforce_team_uniqueness from territory.team_policy on saving;
TerritoryObserver re-syncs children on policy change.
Validation on the position: (a) team.kind MUST equal territory.team_policy;
(b) strict territories reject a 2nd active position for the same team.
Filament team picker filtered to teams whose kind == the territory's team_policy.
Add RepScope::invoiceablePositions() (productsForPosition comes in Phase 5).

Filament (office panel): Positions resource + Assignments relation manager (assign a
rep; end via effective_to). Show occupant + a VACANT badge.

Pest (MUST pass): strict rejects 2nd active position for same team (DB + rule);
liberal allows it; position rejects a team whose kind != territory policy (both
directions); one open assignment per position; invoiceablePositions returns the
rep's active positions in a territory. Stop and show me migration, observers, rules,
filtered picker, tests green.
```

### Phase 3 — Master data (incl. product↔team membership)
```
Panel: office (admin) for full CRUD. Customers and demand_creators are also
REFERENCED (via selects) from the field panel's Call/Distribution forms in later
phases — they don't need a field CRUD resource.

Schema: products (name, sku, pack_size, unit_price nullable, active) — NO team_id;
product_team pivot (product_id, team_id); customers (territory_id, name, type,
address, phone); demand_creator_types (lookup, seed the 6 types); demand_creators
(type_id, territory_id, name, affiliation, phone, address).

Product↔team is many-to-many. Enforce on attach: a product may belong to AT MOST ONE
strict team, and any number of liberal teams (§2.0); surface as a clean Filament
error.

Filament (office panel): Products resource with a teams multi-select (group/badge by
kind) + the ≤1-strict-team rule; resources for customers, demand_creator_types,
demand_creators. Seeders with realistic Nigerian data (build the AA–FF / Team A–D
example) + factories + tests.

Pest: a product can join multiple teams; a product is rejected from a 2nd strict
team; a product may join multiple liberal teams. Stop and confirm.
```

### Phase 4 — Call activities
```
Panels: field (reps create/see own) and management (read-only, scoped drill-down).

Schema: calls (user_id, position_id, territory_id [denormalised], demand_creator_id,
call_type enum physical_visit|phone_call, called_at, latitude/longitude nullable,
notes) + call_product pivot.

On create, derive territory_id from the rep's active position (block if none). Apply
ScopesToViewer.

Filament: Calls resource registered in BOTH panels. In field, reps log their own
(user_id forced to auth user, never client-set); demand-creator and product selects
scoped to the rep's territory. In management, read-only and scoped to region/all.

Pest: rep only creates/sees own; supervisor/regional head sees region read-only;
territory derived not client-set; management registration is read-only. Stop and confirm.
```

### Phase 5 — Distributions (invoices) + product guard
```
Panels: field (reps create/see own) and management (read-only, scoped).

Schema: distributions (user_id, position_id, territory_id, team_id, customer_id,
invoice_number, invoice_date, total_amount, status enum draft|posted|void, notes)
+ distribution_lines (distribution_id, product_id, quantity, unit_price, line_amount).

Rules:
- Created under ONE of the rep's active positions (RepScope::invoiceablePositions);
  team_id copied from that position; single-team.
- Each line's product MUST belong to that position's team's product set
  (RepScope::productsForPosition). Reject otherwise — the "can't distribute the same
  product" guard; holds in strict, passes in liberal.
- line_amount = quantity * unit_price; total_amount = sum of lines (recompute server
  side, never trust client). customer must belong to the territory.
- only 'posted' counts toward attainment; 'void' excluded.

Filament: Distributions resource in field (position select filtered to invoiceable
positions; line product select filtered to that position's team's products; posted
total shown) and management (read-only, scoped). Apply ScopesToViewer.

Pest (critical): strict rep blocked from a product outside their position's team;
liberal rep allowed across their liberal team's products; totals recomputed server
side; void excluded from a sample attainment sum. Stop and show me the guard + tests green.
```

### Phase 6 — Deposits & reconciliation
```
Panels: field (reps record a deposit they collected) and office (Accountant manages,
allocates, reconciles). Reconciliation status also surfaces read-only on the
management dashboard (Phase 8).

Schema: deposits (customer_id, territory_id, received_by_user_id, amount,
deposit_date, reference, bank, channel, status enum unreconciled|
partially_reconciled|reconciled|disputed, notes) + deposit_allocations (deposit_id,
distribution_id nullable, amount, allocated_by_user_id, allocated_at).

Deposits are bulk and NOT required to tie to an invoice. Allocations reconcile a
deposit against posted distributions; status derives from allocated vs amount.

Roles: Accountant owns management + reconciliation. Reps may record a deposit they
collected (field). Only Accountant/Admin allocate or mark disputed (office). Apply
ScopesToViewer.

Filament: Deposits resource in field (record only) and office (full manage +
Allocations relation manager + a reconciliation view of unreconciled deposits and
remaining balance). Factories + Pest: status transitions; allocation never exceeds
deposit amount; role gating; field is record-only. Stop & confirm.
```

### Phase 7 — Targets engine (annual, prorated)
```
Read §3 — targets are ANNUAL volumes, mid-cycle changes are a PRORATED BLEND.
Panel: office (admin manages cycles/tiers/assignments). Attainment surfaces as
widgets in field (my targets) and management (team attainment) in Phase 8.

Schema: cycles (name, starts_on, ends_on, is_current); target_tiers;
target_tier_lines (tier_id, product_id, annual_volume); target_assignments (cycle_id,
user_id, position_id nullable, target_tier_id nullable, basis enum tier|custom,
effective_from, effective_to nullable, reason enum, notes); target_assignment_lines
(assignment_id, product_id, annual_volume); rep_monthly_targets (cycle_id, user_id,
year_month, product_id, target_qty) UNIQUE(cycle,user,year_month,product).

Services:
- TargetMaterializer::rebuild(rep, cycle): for each month M, find the assignment
  active on the 1st of M, resolve per-product annual_volume (custom ->
  assignment_lines; tier -> tier_lines, overridden per-product by assignment_lines),
  write target_qty = annual * (1/12). Divisor is ALWAYS 1/12 (full year), NEVER the
  span length.
- AttainmentService: targetYtd, actualYtd (posted distribution_lines), pct, plus
  full-cycle figures, per product and aggregated.
TargetAssignmentObserver queues a rebuild on any create/update/delete.

Filament (office panel): Cycles, Target Tiers (+annual lines), Target Assignments
(pick a rep, a tier OR custom annual lines, an effective range, a reason).

Pest (build the §3.4 examples exactly): Tier 2 = 1200/yr; maternity 0/yr May–Jul;
resumes Aug -> effective annual 900, YTD end-of-Aug 400. Tier change 1200 -> 1500
effective Aug -> effective annual 1350; pre-Aug months stay at 100/mo. Stop and show
me the materializer, attainment service, both tests green.
```

### Phase 8 — Dashboards & reports (per panel)
```
Role-scoped dashboards/widgets; all reads go through ScopesToViewer.

field panel (Rep): my YTD attainment per product (vs materialised target), my call
count by type this month, recent distributions, outstanding deposits.
management panel (HQ Lead / Regional Head): region/company leaderboard (attainment
%), call coverage by territory, vacant positions, strict coverage gaps, deposit
reconciliation status, read-only drill-downs.
office panel (Platform Admin): company roll-up by region->territory->team, top/bottom
reps, distribution-value trend; (Accountant): unreconciled deposits, reconciliation
aging.

Add exportable CSV reports for the same cuts. Keep queries indexed; prefer SUMs over
rep_monthly_targets. Include the §2.5 vacancy/coverage queries as widgets. Pest: each
widget returns only data the viewer may see, in the right panel. Stop & confirm.
```

### Phase 9 — Hardening
```
Final pass, no new features:
1. Policies for every resource; audit that visibility (scopeVisibleTo), write
   (Policy), AND canAccessPanel gating are all enforced and tested for all 7 roles
   across the three panels. Verify shared resources behave correctly in each panel.
2. spatie/laravel-activitylog on positions, assignments, product_team changes,
   distributions, deposits, target_assignments.
3. Performance: composite indexes for hot paths (distribution_lines(product_id) +
   distributions(user_id, invoice_date, status); calls(user_id, called_at);
   rep_monthly_targets(cycle_id, user_id, year_month); product_team(team_id)).
   Profile the heaviest dashboard query and tune.
4. Data integrity: confirm denormalised territory_id/team_id are written via
   observers/mutators, never the client; add a console command to re-verify them and
   re-check the ≤1-strict-team invariant.
5. Seed a realistic demo dataset (regions, strict + liberal territories, AA–FF /
   Team A–D catalog, positions, reps, a full cycle of annual targets with one
   maternity adjustment, calls, distributions, deposits) for UAT.
6. Raise Pest coverage on business rules to near-100%; wire CI.
Stop and give me a coverage summary + the index list + the integrity command.
```

---

## 7. Sequencing notes
- Phases 0–3 are the spine. Phase 0 stands up the three panels + `canAccessPanel`; everything after registers resources into the right panel(s). The strict guarantee spans Phase 2 (kind-match + `UNIQUE(territory,team)`) **and** Phase 3 (the ≤1-strict-team rule) — both must be in place before Phase 5's distribution guard can rely on it.
- 4/5/6 are independent of each other once 3 exists; parallelise if you have help.
- 7 depends on 3 (products) and 5 (posted distributions for actuals).
- Treat each "stop and confirm" as a real gate: review the migration and the named tests before moving on. That review loop is the single biggest lever on output quality with an AI agent.
