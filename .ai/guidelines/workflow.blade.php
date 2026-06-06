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