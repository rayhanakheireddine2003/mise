<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\StorageArea;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PriceService;
use App\Services\UnitConverter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * MISE-11: demo data for a small Lebanese grill, so the app isn't empty.
 *
 * Rebuild everything with:  php artisan migrate:fresh --seed
 *
 * Note: we do NOT use "WithoutModelEvents" here, so the same rules as in the
 * app apply (e.g. the RecipeLine rule from MISE-10).
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->users();
        $areas = $this->storageAreas();
        $ingredients = $this->ingredients($areas);
        $this->suppliersAndPrices($ingredients);
        $this->recipes($ingredients);
    }

    /** One login per role. The password for all three is "password". */
    private function users(): void
    {
        foreach (['owner' => 'Owner', 'chef' => 'Chef', 'storekeeper' => 'Storekeeper'] as $role => $name) {
            User::firstOrCreate(['email' => "{$role}@mise.test"], [
                'name' => $name,
                'role' => $role,
                'password' => Hash::make('password'),
                'email_verified_at' => now(), // the dashboard needs a verified email
            ]);
        }
    }

    /** @return array<string, StorageArea> */
    private function storageAreas(): array
    {
        $areas = [];
        foreach (['Cold room', 'Dry store', 'Freezer'] as $name) {
            $areas[$name] = StorageArea::firstOrCreate(['name' => $name]);
        }

        return $areas;
    }

    /** @return array<string, Ingredient> */
    private function ingredients(array $areas): array
    {
        // name => [base unit, storage area, grams per piece, pack sizes]
        $list = [
            'Chicken breast' => ['g', 'Cold room', null, []],
            'Garlic' => ['g', 'Cold room', null, []],
            'Vegetable oil' => ['ml', 'Dry store', null, ['tin' => 4000]],
            'Lemon' => ['piece', 'Cold room', 120, []],
            'Salt' => ['g', 'Dry store', null, []],
            'Saj bread' => ['piece', 'Dry store', null, ['pack' => 10]],
            'Frozen fries' => ['g', 'Freezer', null, ['bag' => 2500]],
            'Pickles' => ['g', 'Dry store', null, []],
        ];

        $ingredients = [];
        foreach ($list as $name => [$baseUnit, $area, $gramsPerPiece, $packs]) {
            $ingredient = Ingredient::firstOrCreate(['name' => $name], [
                'base_unit' => $baseUnit,
                'storage_area_id' => $areas[$area]->id,
                'grams_per_piece' => $gramsPerPiece,
            ]);

            foreach ($packs as $packName => $qty) {
                $ingredient->units()->firstOrCreate(['name' => $packName], ['qty_in_base_unit' => $qty]);
            }

            $ingredients[$name] = $ingredient;
        }

        return $ingredients;
    }

    /**
     * Prices go through PriceService::record(), which uses the UnitConverter
     * to work out the price per g / ml / piece, exactly like the price form.
     */
    private function suppliersAndPrices(array $ingredients): void
    {
        $hamra = Supplier::firstOrCreate(['name' => 'Hamra Poultry'], ['phone' => '01 345 678']);
        $market = Supplier::firstOrCreate(['name' => 'Central Market'], ['phone' => '01 876 543']);
        $prices = new PriceService;

        // [ingredient, supplier, price, per unit, valid from]
        $list = [
            // Chicken went up from $5 to $6 per kg: shows the price history.
            ['Chicken breast', $hamra, 5.00, 'kg', '2026-11-01'],
            ['Chicken breast', $hamra, 6.00, 'kg', '2026-11-16'],
            ['Garlic', $market, 4.00, 'kg', '2026-11-01'],
            ['Vegetable oil', $market, 20.00, 'tin', '2026-11-01'],
            ['Lemon', $market, 2.40, 'kg', '2026-11-01'],
            ['Salt', $market, 0.80, 'kg', '2026-11-01'],
            ['Saj bread', $market, 1.50, 'pack', '2026-11-01'],
            ['Frozen fries', $market, 7.00, 'bag', '2026-11-01'],
            ['Pickles', $market, 3.00, 'kg', '2026-11-01'],
        ];

        foreach ($list as [$name, $supplier, $price, $unit, $from]) {
            $ingredient = $ingredients[$name];
            // Don't add the same price twice if the seeder runs again.
            $exists = $ingredient->prices()
                ->whereDate('effective_from', $from)->where('unit', $unit)->exists();

            if (! $exists) {
                $prices->record($ingredient->fresh(), $supplier->id, $price, $unit, $from);
            }
        }
    }

    /**
     * Garlic sauce (toum) is a sub-recipe used inside two dishes.
     * Recipe quantities are stored in the base unit (architecture rule 2),
     * converted here with the UnitConverter, just like a form would.
     */
    private function recipes(array $ingredients): void
    {
        $toum = Recipe::firstOrCreate(['name' => 'Garlic sauce (toum)'], [
            'is_sub_recipe' => true, 'yield_qty' => 1000, 'yield_unit' => 'g',
        ]);
        $this->lines($toum, $ingredients, [
            ['Garlic', 0.2, 'kg'],
            ['Vegetable oil', 0.7, 'l'],
            ['Lemon', 2, 'piece'],
            ['Salt', 10, 'g'],
        ]);

        $platter = Recipe::firstOrCreate(['name' => 'Shish taouk platter'], [
            'menu_price' => 12.00, 'target_cost_pct' => 32,
        ]);
        $this->lines($platter, $ingredients, [
            ['Chicken breast', 250, 'g'],
            ['Frozen fries', 200, 'g'],
            ['Pickles', 40, 'g'],
            ['Saj bread', 1, 'piece'],
        ], toum: [$toum, 60]);

        $sandwich = Recipe::firstOrCreate(['name' => 'Taouk sandwich'], [
            'menu_price' => 5.00, 'target_cost_pct' => 30,
        ]);
        $this->lines($sandwich, $ingredients, [
            ['Chicken breast', 120, 'g'],
            ['Pickles', 20, 'g'],
            ['Saj bread', 1, 'piece'],
        ], toum: [$toum, 30]);
    }

    /**
     * Adds lines to a recipe (only if it has none yet).
     * $toum = [sub-recipe, grams] adds the garlic sauce as a sub-recipe line.
     */
    private function lines(Recipe $recipe, array $ingredients, array $list, ?array $toum = null): void
    {
        if ($recipe->lines()->exists()) {
            return;
        }

        $converter = new UnitConverter;

        foreach ($list as [$name, $qty, $unit]) {
            $ingredient = $ingredients[$name];
            $recipe->lines()->create([
                'ingredient_id' => $ingredient->id,
                'qty' => $converter->toBase($ingredient, $qty, $unit),
                'unit' => $ingredient->base_unit,
            ]);
        }

        if ($toum) {
            [$subRecipe, $grams] = $toum;
            $recipe->lines()->create([
                'sub_recipe_id' => $subRecipe->id,
                'qty' => $grams,
                'unit' => $subRecipe->yield_unit,
            ]);
        }
    }
}
