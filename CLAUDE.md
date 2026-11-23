# Mise: project plan and context

Mise is a recipe-costing and stock-variance web app for independent restaurants. It is built by a 4-person student team in the TechTalks Software Engineering Internship (November 2026), on the PHP / Laravel track. The whole team is new to web development, so keep code simple, conventional and well commented.

## The product in one line

**Price change → re-cost every affected dish → margin alert**, plus **weekly stock count → variance report**.

- Restaurants record supplier prices, recipes (including nested sub-recipes) and stock counts.
- When a supplier price changes, every recipe that uses that ingredient is re-costed. The owner gets an alert if a dish's food-cost % goes above its target.
- Variance: theoretical usage (sales × recipe quantities) vs. actual usage (opening + purchases − closing).

**Users and roles**

| Role | Does |
| --- | --- |
| `owner` | Sees everything, pays for the product, reviews reports and alerts |
| `chef` | Ingredients, storage areas, recipes |
| `storekeeper` | Suppliers, prices, deliveries, stock counts |

**MVP success criteria**

- A chef can enter a 10-ingredient recipe in under 5 minutes.
- A full stock count for a small kitchen takes under 30 minutes.
- Re-costing is correct for 100% of test recipes, including nested ones.

**Out of scope for the MVP:** POS integration or CSV sales import, email alerts, multi-branch, purchase orders, supplier price comparison, menu engineering.

## Tech stack and decisions

| Area | Choice |
| --- | --- |
| Framework | Laravel 13, PHP 8.3+ |
| Auth and layout | Laravel Breeze (Blade stack): login, register, profile, `<x-app-layout>` |
| Front end | Blade templates + Tailwind (via Vite). No JS framework |
| Database | **MySQL** for local dev (XAMPP's MySQL, database `mise`, user `root`, no password) and for deployment. Tests run on SQLite in memory (`phpunit.xml`) for speed. Use migrations only, no raw database-specific SQL, so both keep working |
| Tests | PHPUnit, `php artisan test`, `RefreshDatabase`. Feature tests in `tests/Feature`. Locally they use an in-memory SQLite database, so they never touch your MySQL data |
| CI | GitHub Actions `.github/workflows/tests.yml`, PHP 8.4, two jobs that must both be green: `tests` (SQLite in memory) and `tests-mysql` (real MySQL 8.4 server, also checks migrations roll back) |
| Money | USD, stored as decimals |
| Local dev | Start MySQL in the XAMPP Control Panel, then two terminals: `php artisan serve` and `npm run dev`. Avoid `composer run dev`, because `pail` doesn't work on Windows. PHP comes from php.new (8.5), not XAMPP's PHP 8.2, which is too old |

## Architecture rules

1. **Thin controllers.** Business rules live in `app/Services` (or model events) so they can be unit tested.
2. **Every quantity is stored in the ingredient's base unit**: `g`, `ml` or `piece`. Convert only at input and display, via `App\Services\UnitConverter`.
3. **Prices are never updated or deleted.** A new price = a new `ingredient_prices` row with `effective_from`. Historical reports use the price in effect on that date (`App\Services\PriceService::priceAt`).
4. **A recipe line has exactly one of** `ingredient_id` or `sub_recipe_id`. A recipe can't contain itself. Enforced in the `RecipeLine::saving` model event (throws `DomainException`). Full cycle detection (A → B → A) comes in Sprint 2 with costing.
5. **Authorization by role middleware:** `->middleware('role:owner,chef')`, implemented by `App\Http\Middleware\EnsureUserHasRole` and registered as the alias `role` in `bootstrap/app.php`. `User::hasRole(string ...$roles): bool`.
6. Validation is done in controllers with `$request->validate([...])`. Errors are shown with `<x-input-error>`. Success messages use `->with('status', '...')`, and refusals use `->with('error', '...')`.
7. Forms: `@csrf` is always present, `@method('PUT'|'DELETE')` where needed, one `form.blade.php` for create and edit (switching on `$model->exists`), and `old('field', $model->field)` to keep values after errors.

## Database schema

All Mise tables are in one migration, `create_mise_tables`, plus `add_role_to_users_table`.

| Table | Columns |
| --- | --- |
| users (+) | `role` string, default `storekeeper` |
| storage_areas | id, name (unique) |
| ingredients | id, name (unique), base_unit (`g`/`ml`/`piece`), grams_per_piece (nullable decimal), storage_area_id (nullable FK, nullOnDelete) |
| ingredient_units | id, ingredient_id (FK cascade), name (e.g. `case`), qty_in_base_unit; unique(ingredient_id, name) |
| suppliers | id, name (unique), phone (nullable) |
| ingredient_prices | id, ingredient_id (FK cascade), supplier_id (FK restrict), price, unit, price_per_base_unit, effective_from (date); index(ingredient_id, effective_from) |
| purchases | id, supplier_id, invoice_no (nullable), purchased_on (date), user_id (nullable) |
| purchase_lines | id, purchase_id (FK cascade), ingredient_id, qty, unit, unit_price |
| recipes | id, name (unique), is_sub_recipe (bool), yield_qty (default 1), yield_unit (default `portion`), waste_pct (default 0), menu_price (nullable), target_cost_pct (default 30) |
| recipe_lines | id, recipe_id (FK cascade), ingredient_id (nullable), sub_recipe_id (nullable FK → recipes), qty, unit |
| stock_counts | id, counted_on (date), status (`draft`/`done`), user_id (nullable) |
| count_lines | id, stock_count_id (FK cascade), ingredient_id, qty_in_base_unit; unique(stock_count_id, ingredient_id) |
| sales | id, recipe_id, sold_on (date), qty |
| margin_alerts | id, recipe_id (FK cascade), old_cost_pct, new_cost_pct, seen (bool) |

**Models** (in `app/Models`): StorageArea, Ingredient (`BASE_UNITS = ['g','ml','piece']`; `storageArea()`, `units()`, `prices()`), IngredientUnit, Supplier, IngredientPrice (casts: effective_from date, price decimal:4, price_per_base_unit decimal:6), Purchase, PurchaseLine, Recipe (`lines()`), RecipeLine (`recipe()`, `ingredient()`, `subRecipe()`), StockCount, CountLine, Sale, MarginAlert.

## Key services

**`App\Services\UnitConverter`**
- `STANDARD_UNITS`: g (mass, 1), kg (mass, 1000), ml (volume, 1), l (volume, 1000), piece (count, 1).
- `toBase(Ingredient, float $qty, string $unit): float` checks the ingredient's pack sizes first, then the standard units. Pieces ↔ grams works only if `grams_per_piece` is set. It throws `InvalidArgumentException` for negative quantities, unknown units and impossible conversions (e.g. kg → ml).
- `convert(Ingredient, qty, from, to)` and `unitsFor(Ingredient): array` (for dropdowns).

**`App\Services\PriceService`**
- `priceAt(Ingredient, string $date): ?IngredientPrice` returns the newest price with `effective_from <= date`. Ties on the same date go to the highest id.

## Routes

`routes/web.php` has one block per member inside the `auth` group:

| Role middleware | Routes |
| --- | --- |
| `role:owner,chef` | `storage-areas` (index, store, destroy); `ingredients` resource (except show); `POST ingredients/{ingredient}/units` → `ingredients.units.store`; `DELETE ingredient-units/{unit}` → `ingredient-units.destroy`; `GET recipes` → `recipes.index` |
| `role:owner,storekeeper` | `suppliers` resource (except show); `GET prices` → `prices.overview`; `GET ingredients/{ingredient}/prices` → `ingredients.prices.index`; `POST ingredients/{ingredient}/prices` → `ingredients.prices.store` |

Navigation (`layouts/navigation.blade.php`) shows each link only if `auth()->user()->hasRole(...)` and `Route::has(...)`.

## Demo data (DatabaseSeeder)

- Users `owner@mise.test`, `chef@mise.test` and `storekeeper@mise.test`. The password for all three is `password`.
- Storage areas: Cold room, Dry store, Freezer.
- Ingredients: Chicken breast, Garlic, Vegetable oil (tin = 4000 ml), Lemon (120 g/piece), Salt, Saj bread (pack = 10), Frozen fries (bag = 2500 g), Pickles.
- Suppliers: Hamra Poultry, Central Market. Chicken costs $5.00/kg from 2026-11-01 and $6.00/kg from 2026-11-16, to show price history.
- Recipes: "Garlic sauce (toum)" is a sub-recipe yielding 1000 g. It is used inside "Shish taouk platter" ($12, target 32%) and "Taouk sandwich" ($5, target 30%).
- To rebuild: `php artisan migrate:fresh --seed`.

## Five-week timeline

| Week | Sprint | Goal |
| --- | --- | --- |
| 1 | Sprint 0 | Validation and design: 5+ restaurant interviews, competitor review, user stories in Jira, ERD, repo setup |
| 2 | **Sprint 1** | Foundation: auth and roles, unit converter, ingredients and pack sizes, storage areas, suppliers, price history, all tables, demo data |
| 3 | Sprint 2 | Core loop: recipe builder with nested sub-recipes, recursive plate cost and cycle detection, purchase entry → effective-dated price → re-cost → margin alert, mobile stock count screen, recipes list |
| 4 | Sprint 3 | Sales entry, variance report, automated tests on business rules, test with 1–2 real users. Feature freeze at the end of the week |
| 5 | Polish | Bug fixes, usability, realistic demo data, README, decision notes, rehearse the demo, retrospective |

## Sprint 1: tickets and owners (3 hours per member) — ✅ completed 2026-11-23

| Ticket | Title | Owner | Status |
| --- | --- | --- | --- |
| MISE-1 | Project setup (Breeze, CI), all migrations and models, route blocks | Member 4 | ✅ Done |
| MISE-2 | Role middleware + `role` alias + UnitConverter (merged first = Gate B) | Member 1 | ✅ Done |
| MISE-3 | RoleMiddlewareTest (3) + UnitConverterTest (8) + role-based menu | Member 1 | ✅ Done |
| MISE-4 | Storage areas page | Member 2 | ✅ Done |
| MISE-5 | Ingredients list / add / edit / delete, search and area filter | Member 2 | ✅ Done |
| MISE-6 | Pack sizes + IngredientTest (6) | Member 2 | ✅ Done |
| MISE-7 | Suppliers list / add / edit / delete (refuse delete if it has prices) | Member 3 | ✅ Done |
| MISE-8 | PriceService + priceAt test | Member 3 | ✅ Done |
| MISE-9 | Price overview, history and new-price form + 3 more PriceTest tests | Member 3 | ✅ Done |
| MISE-10 | RecipeLine rule + RecipeLineTest (5) | Member 4 | ✅ Done |
| MISE-11 | Demo data seeder (needs UnitConverter) | Member 4 | ✅ Done |
| MISE-12 | Recipes list, read-only | **Moved to Sprint 2** | |

**Gates**
- **Gate A:** Member 4 pushes the project and all tables and models straight to `main`. Nobody else can clone before this.
- **Gate B:** Member 1 merges the role middleware and UnitConverter. MISE-9 and MISE-11 wait for it.

**Sprint 1 is done when:** (checked on 2026-11-23; built by Member 4 alone, the team splits the work again from Sprint 2)
- [x] All tickets are merged into `main` and CI is green.
- [ ] `php artisan migrate:fresh --seed` works on all four laptops. (Works in CI on SQLite and MySQL; still to check on each laptop.)
- [x] Each role logs in and sees only its own menu items. A storekeeper gets a 403 on `/ingredients`.
- [x] A chef can add an ingredient with a pack size. A duplicate name shows an error.
- [x] A storekeeper can record a new chicken price, and the old price stays in the history.
- [x] Recording chicken "per ml" shows an error instead of saving.
- [x] `php artisan test` passes (74 tests: 49 team tests plus Breeze's 25).

## Team workflow

- Never push to `main` directly. The one exception is the initial project push for Gate A.
- One branch per ticket: `feat/MISE-<n>-<short-name>` or `fix/...`. Small PRs (one page or feature, under about 300 lines), one reviewer, CI green before merging.
- After every `git pull`, run `composer install`, `npm install` and `php artisan migrate`.
- Each member adds routes only under their own comment block in `routes/web.php`, to avoid conflicts.
- Never commit `.env`, `vendor/`, `node_modules/` or `*.sqlite`.
- Before starting work, open the XAMPP Control Panel and start **MySQL** (Apache isn't needed).
- Tools: GitHub (code + PRs), Jira (tickets and sprints), Notion (interview notes, decisions, retros).

## Instructions for Claude Code

- Follow the conventions above. Don't introduce new packages, frameworks or a JS front end without being asked.
- Put business logic in `app/Services`, and add a feature test for every business rule.
- Use Breeze components (`x-app-layout`, `x-text-input`, `x-input-label`, `x-input-error`, `x-primary-button`, `x-secondary-button`, `x-danger-button`) and plain Tailwind classes.
- Explain changes in beginner-friendly terms: the team is building its first web app.
- Run `php artisan test` after changes and keep it green.
