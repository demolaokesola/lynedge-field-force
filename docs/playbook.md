# Pharma Sales & Demand-Creation Tracker — Build Playbook (v2: outstanding work)
**Stack:** Laravel 13 · Filament 5 (Livewire 4 / Tailwind 4) · PostgreSQL · Pest · Laravel Sail · Laravel Boost
**Context:** Rebuild of an Oracle APEX field-force tracker for a Nigerian pharma company.
**Status (2026-09-19):** Phases 0–8 of the original playbook are built, committed and green (`329 passed, 818 assertions`). This rewrite records what exists, what changed along the way, and the phases still to run.

> Filament 5 is **functionally identical to Filament 4** — write v4-idiom Filament and let Boost's `search-docs` confirm exact signatures. The domain invariants, the two visibility scopes, and the per-phase workflow live in `.ai/guidelines/project.blade.php` and `.ai/guidelines/workflow.blade.php` (rendered into `CLAUDE.md`). **Those files are the source of truth** — this playbook does not repeat them.

---

## 0. Where the build stands

### 0.1 Done (original Phases 0–8)

| Area | What exists | Where to look |
|---|---|---|
| Foundation | spatie/permission + Shield (`superuser` = Shield super-admin, bypass via `Gate::before`); three panels `field` / `office` / `management` with `User::canAccessPanel()`; `MoneyCast`; enums for every status/kind column; activitylog package installed (table migrated, **nothing logs yet**) | `app/Providers/Filament/*`, `app/Models/User.php`, `app/Casts/MoneyCast.php`, `app/Enums/*` |
| Org & RBAC | regions, territories (`team_policy`), teams (`kind`), users (`region_id`, `is_active`); roles seeded; `scopeVisibleOrgTo` on Region/Territory | `app/Models/{Region,Territory,Team}.php`, `database/seeders/RolesSeeder.php` |
| Positions | positions + position_assignments with both partial unique indexes; `PositionObserver` syncs `enforce_team_uniqueness` and **derives `code` as `{territory.code}-{team.code}`**; `TerritoryObserver`/`TeamObserver` re-sync children; kind-match + strict-uniqueness rules; Office resource + Assignments relation manager; **read-only Positions resource in management** | `app/Observers/*`, `app/Filament/Office/Resources/Positions`, `app/Filament/Management/Resources/Positions` |
| Master data | products, product_team pivot (`TeamMembership` pivot model enforces ≤1 strict team), customers, demand_creator_types (seeded), demand_creators; `ScopesToTerritory` on Customer/DemandCreator; `created_by` on both | `app/Models/Relations/TeamMembership.php`, `app/Models/Concerns/ScopesToTerritory.php` |
| Calls | calls + call_product; territory derived from active position; `ScopesToViewer`; shared resource registered in field (write own) + management (read-only) | `app/Filament/Shared/Resources/Calls` |
| Distributions | distributions + lines; created under one invoiceable position, `team_id` copied; product-team guard (`RepScope::productsForPosition`); **unit_price authoritative from product**; totals recomputed server-side; Draft → Posted (submit action) → Void | `app/Filament/Shared/Resources/Distributions`, `app/Services/RepScope.php` |
| Deposits | deposits only; one-to-one reconciliation against a bank-statement entry (reconciled_at/by, statement_date/reference on the row); status unreconciled / reconciled / disputed (only an unreconciled deposit can be disputed; disputing requires a `dispute_reason`, shown to the rep on the View page); field (record + read-only view) + office (manage/reconcile, Bank Reconciliation page) | `app/Filament/Shared/Resources/Deposits` |
| Targets | cycles, target_tiers, target_tier_lines, target_assignments, target_assignment_lines, rep_monthly_targets; `TargetMaterializer` (1/12 divisor, mid-month fix landed), `AttainmentService`, `TargetAssignmentObserver` → `RebuildRepMonthlyTargetsJob`; Office resources incl. a **products × tiers volume grid** (`TierVolumesGrid`, native table-Repeater) | `app/Services/TargetMaterializer.php`, `app/Services/AttainmentService.php`, `app/Filament/Office/Resources/TargetTiers` |
| Dashboards & exports | Field: YTD attainment, rep performance overview, call summary, recent distributions, outstanding deposits, stale customers, no-position / no-cycle / supervisor-scope notices. Management: attainment leaderboard, call coverage, vacant positions, strict coverage gaps, reconciliation status. Office: company roll-up, top/bottom reps, distribution trend, unreconciled deposits, reconciliation aging. CSV exporters for six cuts | `app/Filament/{Field,Management}/Widgets`, `app/Filament/Widgets`, `app/Filament/Exports` |
| Tests | 53 Pest files across Calls, Customers, Dashboards, DemandCreators, Deposits, Distributions, MasterData, Org, Panels, Products, Stock, Targets | `tests/Feature/*` |

### 0.2 Built beyond the original playbook

These were added during Phases 1–8 and are now part of the product. Prompts below assume them.

1. **No `supervisor` role.** Supervisory read is derived from `Position.supervisor_id` (set by platform_admin in Office). `User::isSupervisor()`, `RepScope::positionsSupervisedBy()`, `RepScope::subordinateUserIds()`. Roles are the six in `RolesSeeder`. `canAccessPanel`: field → `sales_rep`; office → `platform_admin|accountant`; management → `hq_lead|regional_head`; `superuser` → all.
2. **Panel-namespaced resources.** `app/Filament/Field`, `Office`, `Management` hold panel-specific resources; `app/Filament/Shared` holds the resources registered in more than one panel (Calls, Deposits, Distributions). The Office panel discovers `app/Filament/Widgets`.
3. **Field panel has its own customer-data cluster** (`CustomerDataCluster`): reps create/view Customers and Demand Creators scoped to their territory (with `created_by`), and get a read-only Products view. The original "no field CRUD for master data" assumption was reversed.
4. **`Position.label` dropped; `Position.code` derived** from territory + team codes, with collision avoidance. Position rep/supervisor selects are restricted to eligible reps.
5. **Centralised login** at `/` (`LoginController`) that redirects each user to their home panel (`User::defaultPanelId()`), with logout/timeout routed back to it. Shield's own plugin UI is removed from all panels; roles are assigned through the Users resource + `RolePolicy`.
6. **Stock management module** (Phase S in the original numbering never existed — it was built after Phase 8):
   - Tables: `stock_dispatches` (+lines), `stock_adjustments` (+lines), `stock_movements` (immutable ledger), `position_product_stocks` (materialised balance, `UNIQUE(position_id, product_id)`).
   - Flow: platform_admin ("Operations") creates a dispatch to a position (Draft → Dispatched → Accepted / Void); the occupying rep accepts it into their balance; Operations posts signed adjustments (damage, loss, correction, return, recall). Every change goes through `StockLedger::record()` — the only writer of `stock_movements` and balances.
   - `ScopesToPosition` trait; `StockDispatchPolicy` / `StockAdjustmentPolicy`; Office resources for dispatches/adjustments; Field `MyStockCluster` (accept dispatches, view levels, view adjustments).
   - **Gaps:** `StockMovementType` is only `dispatch_acceptance | adjustment` — posting a distribution does **not** consume stock; balances may go negative; no stock widgets on any dashboard; no management read; no exports. See Phase 10.
7. **Filament import/export tables** + `notifications` table are migrated (used by the CSV exporters).

### 0.3 Not done

- **Original Phase 9 (hardening)** — nothing started: no `LogsActivity` on any model, no integrity command, no CI, demo seeding stops at positions (`DatabaseSeeder` has `MasterDataSeeder` commented out; no cycles/targets/calls/distributions/deposits/stock seeded), several hot-path indexes missing.
- **Stock module completion** (sales consumption, dashboards, management read).
- **Original Phase 10 (AI weekly brief)** — not started.

---

## 1. Standing rules

Read `.ai/guidelines/project.blade.php` before every phase. Non-negotiables, restated only by title so you know they exist:
Position = (territory, team) with kind-match + strict uniqueness · ≤1 strict team per product · single-team distributions with the product guard · annual targets, prorated blend, **divisor always 1/12** · denormalised `territory_id`/`team_id` at write time · two scopes (`scopeVisibleTo` vs `scopeVisibleOrgTo`) never conflated · a panel is navigation, never authorization · no `supervisor` role.

Per-phase workflow (`.ai/guidelines/workflow.blade.php`): inspect with Boost (`database-schema`, `list-models`) → `php artisan test` green → restate the slice → build (migration → models/enums → Filament in the named panel[s] → named Pest tests) → `php artisan test` → summarise diff + show output → **STOP** and wait for review.

---

## 2. Outstanding phases — paste one block per turn

The original Phase 9 was one giant slice; it is split into 9A–9F so each has a clean review gate. Phase 10 closes the stock module. Phase 11 is the optional AI brief. Standing instruction for every prompt: *use Boost `search-docs` for exact Filament 5 / Laravel 13 syntax, and `database-schema` before any migration.*

### Phase 9A — Authorization matrix audit
```
Hardening, no new features. Read .ai/guidelines/* first; run the suite and confirm green.

Goal: prove that for every resource, in every panel it is registered in, all SIX roles
(superuser, platform_admin, accountant, hq_lead, regional_head, sales_rep) plus the
DERIVED supervisor case (a sales_rep named as Position.supervisor_id) get exactly the
intended visibility (scopeVisibleTo / scopeVisibleOrgTo / ScopesToTerritory /
ScopesToPosition), write access (Policy), and panel entry (canAccessPanel).

1. Inventory every Resource class under app/Filament/{Field,Office,Management,Shared}
   and every Policy under app/Policies. List any resource with no policy, or any policy
   method that is missing (viewAny, view, create, update, delete, plus custom actions
   such as post/void/send/accept/allocate).
2. Write ONE table-driven Pest file per panel (tests/Feature/Authorization/
   {Field,Office,Management}AuthorizationMatrixTest.php) using datasets: for each
   (resource, role) pair assert: can/cannot load the list page; sees only the rows the
   scope allows (seed one row in-scope and one out-of-scope); can/cannot reach create/
   edit; custom actions are hidden/forbidden where the policy says so.
3. Shared resources (Calls, Distributions, Deposits) must be asserted in BOTH panels
   they register in; management must be read-only for all roles including superuser.
4. Supervisor case: a rep supervising a position sees its CURRENT occupant's calls/
   distributions/deposits/stock but cannot edit them; a plain rep sees only their own.
5. Fix anything the matrix exposes. Do NOT loosen a scope to make a test pass — flag it.

Stop and show me the inventory (resources × policies), the gaps found and fixed, and
the matrix test output.
```

### Phase 9B — Activity log
```
spatie/laravel-activitylog is installed and migrated but no model logs anything.

Add LogsActivity (logFillable, logOnlyDirty, dontLogEmptyChanges, useLogName per
domain) to: Position, PositionAssignment, Distribution (+ DistributionLine), Deposit,
DepositAllocation, TargetAssignment (+ TargetAssignmentLine), StockDispatch,
StockAdjustment, Territory, Team, Product, User (log role changes via a manual
activity()->... call in the Users resource, not the model).
product_team: the pivot model App\Models\Relations\TeamMembership — log attach/detach
manually from the pivot events so the ≤1-strict-team changes are traceable.
StockMovement is already an immutable ledger — do NOT double-log it.

Causer = auth user; when a job writes (RebuildRepMonthlyTargetsJob) log with no causer
and a descriptive event. Add setDescriptionForEvent so entries read like
"Position LAG-A frozen", "Distribution INV-0042 posted".

Office panel: a read-only Activity resource (platform_admin|superuser only) with
filters by subject type, causer, date. No management/field exposure.

Pest: each listed model writes an activity on create/update/delete with the right
causer; pivot attach/detach logs; the rebuild job logs without a causer; the Activity
resource is invisible to accountant/hq_lead/regional_head/sales_rep. Stop & confirm.
```

### Phase 9C — Indexes & query performance
```
Use Boost database-schema first. Existing indexes already cover: calls(user_id,
called_at), calls(territory_id), distributions(user_id, invoice_date),
distributions(territory_id), product_team(team_id), call_product(product_id),
target_assignments(cycle_id, user_id, effective_from), rep_monthly_targets unique
(cycle_id, user_id, year_month, product_id), stock_*(position_id, status),
stock_movements(position_id, product_id). Postgres does NOT auto-index FK columns.

One migration adding:
- distribution_lines(product_id); distribution_lines(distribution_id, product_id)
- distributions(status, invoice_date) and distributions(user_id, status, invoice_date)
- deposits(territory_id), deposits(customer_id), deposits(status, deposit_date),
  deposits(received_by_user_id)
- deposits(bank_account_id, status)
- customers(territory_id), demand_creators(territory_id), demand_creators(demand_creator_type_id)
- positions(supervisor_id), position_assignments(user_id, effective_from)
- stock_movements(territory_id, created_at)
Skip any that database-schema shows already exist.

Then profile: run EXPLAIN ANALYZE (via Boost database-query) on the queries behind
CompanyRollupWidget, AttainmentLeaderboardWidget, CallCoverageWidget and
YtdAttainmentWidget against the demo dataset (Phase 9E if it exists, else factories
at 5 regions × 4 territories × 3 positions × 12 months). Report before/after plan
costs. Add ->with() eager loads where N+1s appear (use Boost browser-logs / query
counts in tests).

Pest: an assertion per widget that its query count is bounded (no N+1) using
Illuminate\Support\Facades\DB::enableQueryLog or Pest's query-count expectations.
Stop and show me the migration, the index list, and the before/after plans.
```

### Phase 9D — Integrity command
```
Add a console command app:verify-integrity (app/Console/Commands/VerifyIntegrity.php)
that RE-CHECKS every invariant the app enforces at write time and reports drift.
Read-only by default; --fix applies safe corrections; exit code 1 on any finding.

Checks:
1. Denormalised territory_id/team_id on calls, distributions, deposits,
   stock_dispatches, stock_adjustments, stock_movements match the row's position
   (territory via position.territory_id; team via position.team_id).
2. Every position's team.kind == territory.team_policy; enforce_team_uniqueness ==
   (territory.team_policy == strict); position.code == derived code.
3. No product belongs to more than one strict team (product_team × teams.kind).
4. No strict territory has two active positions on the same team; no position has
   two open assignments (defence in depth over the partial indexes).
5. Every distribution_line's product is in its distribution's team's product set;
   line_amount == quantity * unit_price; header total == SUM(lines).
6. Every reconciled deposit carries reconciled_at, reconciled_by_user_id and
   statement_date; every unreconciled deposit carries none of them.
7. rep_monthly_targets: for every (rep, cycle) with assignments, a dry-run
   TargetMaterializer produces the same rows as stored (report diffs; --fix rebuilds).
8. position_product_stocks.quantity == SUM(stock_movements.quantity_delta) per
   (position, product); list negative balances.

Output a table per check with counts; verbose lists ids. Schedule it daily in
routes/console.php (report only) and log findings.

Pest: one test per check that seeds a violation via raw DB writes (bypass the model
guards) and asserts the command reports it and, where --fix is supported, repairs it.
Stop & confirm.
```

### Phase 9E — Demo / UAT dataset
```
DatabaseSeeder currently runs Roles, DemoUsers, DemandCreatorTypes, Regions,
Territories, Positions and has MasterDataSeeder commented out. Finish the demo
dataset for UAT; keep every seeder idempotent (firstOrCreate keyed on natural keys).

IMPORTANT: DatabaseSeeder uses WithoutModelEvents, so observers do NOT fire.
Either drop that trait, or have the seeders call PositionObserver-equivalent code
paths and TargetMaterializer::rebuild() explicitly. Prefer dropping the trait and
seeding through the models so the seed exercises the same guards as production.

Add / enable:
1. MasterDataSeeder (uncomment): products AA–FF, strict Teams A/B, liberal C/D.
2. CustomerSeeder + DemandCreatorSeeder: 3–6 per territory, realistic Nigerian names/
   addresses, created_by = the territory's rep.
3. CycleSeeder: current cycle (starts 1 Feb, ends 31 Jan, is_current) + previous.
4. TargetSeeder: Tier 1/2/3 with annual volumes per product; one tier assignment per
   rep from cycle start; ONE rep with a maternity custom assignment (0 for May–Jul)
   and ONE with a mid-cycle tier change effective Aug (the §3.4 examples); then
   materialise.
5. ActivitySeeder: 8 months of calls (physical/phone mix, products detailed),
   posted distributions that land reps at ~60–130% attainment, a few drafts and one
   void, deposits with a mix of unreconciled / reconciled / disputed statuses.
6. StockSeeder: one accepted dispatch per active position, one draft dispatch, one
   posted adjustment (damage), so field My Stock and Office stock views are populated.
7. DemoUsersSeeder: ensure at least one user per role, one rep who is also a
   supervisor of two positions, and one vacant position.

Provide `sail artisan db:seed --class=DemoSeeder` as the single entry point and
document the demo logins in the seeder's docblock (never real credentials).

Pest: DemoSeeder runs clean twice (idempotent); post-seed counts; the two target
worked-examples produce the expected rep_monthly_targets. Stop & confirm.
```

### Phase 9F — CI
```
Add .github/workflows/ci.yml:
- Trigger: push to main + pull_request.
- Services: postgres:16 with a `testing` database matching phpunit.xml.
- Steps: PHP 8.5 (shivammathur/setup-php with pgsql, intl, bcmath), composer install
  (cached), copy .env.example, key:generate, migrate, `vendor/bin/pint --test`,
  `php artisan test --compact --parallel`.
- Node build only if a resources/js or css change is in the diff (path filter).
Also add a `composer test` script and a `composer lint` script if missing, and a
pre-commit hint in README (no hooks committed).

Verify the workflow runs green on a PR before merging. Stop & confirm with the run URL.
```

### Phase 10 — Stock: close the loop
```
The stock module exists (dispatch -> accept -> ledger -> balance; adjustments) but
a posted distribution does not touch stock, balances can go negative unbounded, and
nothing surfaces on dashboards. Read app/Services/StockLedger.php and
app/Models/StockMovement.php first — StockLedger::record() stays the ONLY writer.

DECISION REQUIRED BEFORE BUILDING (ask me if not stated in this prompt):
  (a) posting a distribution with insufficient stock is BLOCKED, or
  (b) it is ALLOWED and the balance goes negative, flagged on dashboards.
Default to (b) with a clear warning, as the ledger already permits negatives.

1. Add StockMovementType::Sale (and ::SaleReversal). On Distribution posting, write
   one movement per line: quantity_delta = -quantity, source = the DistributionLine,
   caused_by = the posting user, position/territory/team from the distribution.
   On voiding a POSTED distribution, write the reversal. Draft/void transitions that
   never posted write nothing. Wrap posting + movements in one DB transaction.
2. If (a): Distribution post action validates each line against
   position_product_stocks and fails with a per-product message.
3. Field panel: "My stock" summary widget on the dashboard (on-hand per product for
   the rep's active positions, negative in red); StockLevels resource gets a
   movements drill-down (ledger rows for a product, read-only).
4. Office panel: StockOnHandWidget (position × product grid, filter by territory/team,
   negative balances first) + a NegativeBalancesWidget for platform_admin; CSV
   exporter for stock levels and for movements (date range).
5. Management panel: read-only StockLevels resource scoped by scopeVisibleOrgTo
   (regional_head sees their region's positions); a StockCoverageWidget (on-hand vs
   last-3-months average sales per product per territory = weeks of cover).
6. Integrity: extend Phase 9D check 8 to include Sale movements; the materialised
   balance must still equal SUM(quantity_delta).

Pest: posting decrements the right position's balance per line; voiding a posted
distribution restores it; voiding a draft writes no movement; (a) or (b) behaviour per
the decision; every new widget/resource returns only rows the viewer may see, in the
right panel; the exporter respects scope. Stop & confirm.
```

### Phase 11 — AI narrative brief on the management dashboard (optional)
```
Only after Phases 9A–9F are merged and green. Adds a Claude-generated "weekly brief"
to the management panel. AI is read-only: it narrates numbers the widgets already
compute, never writes to any domain table, never bypasses a scope.

Dependency: anthropic-ai/sdk (composer) — ask before adding. ANTHROPIC_API_KEY in
.env only. Bind Anthropic\Client in a service provider so tests can swap a fake.
Use the latest Claude model id from the claude-api skill; do not hard-code a guess.

1. Migration: dashboard_briefs (scope_type enum company|region, scope_id nullable
   region_id, period_start date, period_end date, body text, input_snapshot jsonb,
   model string, generated_at). UNIQUE(scope_type, scope_id, period_start).
2. app/Services/Ai/DashboardBriefService: builds the payload ONLY from the same
   aggregate queries the management widgets use (AttainmentLeaderboard, CallCoverage,
   StrictCoverageGaps, VacantPositions, ReconciliationStatus, and Phase 10
   StockCoverage), scoped by region for a regional brief, company-wide for HQ.
   Aggregates only — never raw rows; pseudonymise reps/customers to ids. Persist the
   payload in input_snapshot. Stable system prompt with prompt caching; structured
   output: headline + 3–6 bullets + flagged territories/positions.
3. GenerateDashboardBriefJob (queued), scheduled Monday 06:00 Africa/Lagos for the
   company and every region. Failure logs and leaves the previous brief in place — a
   missing brief must never break the dashboard.
4. DashboardBriefWidget in management: hq_lead sees the company brief, regional_head
   their region's (users.region_id, same rule as scopeVisibleOrgTo). Show generated_at
   and a "regenerate" action gated to hq_lead.
5. Pest (fake the client; assert on the payload sent, not model output): regional
   payload contains only that region's territories; hq payload is company-wide; a job
   failure preserves the previous brief; widget renders the right brief per role and
   nothing for sales_rep (supervisor or not).
Stop & confirm.
```

---

## 3. Sequencing

- **9A first.** It is the audit everything else leans on; any scope bug found there changes what 9B logs and what 9E seeds.
- **9B, 9C, 9D are independent** once 9A is merged. 9D's check 7 (materialiser dry-run) and check 8 (stock balance) are the two that catch real drift — don't skip them.
- **9E before 9C's profiling step** if you want realistic plans; otherwise 9C can use factories and 9E can come later.
- **9F whenever** — earliest is best; it only needs the suite to be green.
- **10 after 9A and 9D** (it extends the integrity check and the authorization matrix must already cover stock resources). The block-vs-negative decision is yours; the prompt defaults to negative-with-warning.
- **11 strictly last** and only if wanted.
- Treat every "stop and confirm" as a real gate: review the migration and the named tests before saying go.

## 4. Small cleanups (fold into whichever phase touches the file)

- `ManagementPanelProvider` still registers Filament's `AccountWidget` and `FilamentInfoWidget`; drop them.
- `OfficePanelProvider` discovers `app/Filament/Pages`, which does not exist; point it at an Office pages directory or remove the call.
- `tests/Feature/ExampleTest.php` and `tests/Unit/ExampleTest.php` are scaffold placeholders; delete once 9F's CI is green (ask before deleting tests).
- `DatabaseSeeder` — see 9E; decide whether `WithoutModelEvents` stays.
