<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\IngredientPrice;
use App\Models\Recipe;
use App\Models\RecipeLine;
use App\Models\StorageArea;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * MISE-1 smoke test: the Mise tables exist and the model relationships work.
 */
class MiseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_users_are_storekeepers_by_default(): void
    {
        $user = User::factory()->create();

        $this->assertSame('storekeeper', $user->fresh()->role);
    }

    public function test_ingredient_has_area_pack_sizes_and_prices(): void
    {
        $area = StorageArea::create(['name' => 'Cold room']);
        $oil = Ingredient::create(['name' => 'Vegetable oil', 'base_unit' => 'ml', 'storage_area_id' => $area->id]);
        $oil->units()->create(['name' => 'tin', 'qty_in_base_unit' => 4000]);
        $supplier = Supplier::create(['name' => 'Central Market']);
        $oil->prices()->create([
            'supplier_id' => $supplier->id,
            'price' => 20,
            'unit' => 'tin',
            'price_per_base_unit' => 0.005,
            'effective_from' => '2026-11-01',
        ]);

        $oil->refresh();
        $this->assertSame('Cold room', $oil->storageArea->name);
        $this->assertSame('tin', $oil->units->first()->name);
        $this->assertSame('2026-11-01', $oil->prices->first()->effective_from->toDateString());
    }

    public function test_recipe_line_can_point_to_a_sub_recipe(): void
    {
        $toum = Recipe::create(['name' => 'Garlic sauce (toum)', 'is_sub_recipe' => true, 'yield_qty' => 1000, 'yield_unit' => 'g']);
        $sandwich = Recipe::create(['name' => 'Taouk sandwich', 'menu_price' => 5]);
        $sandwich->lines()->create(['sub_recipe_id' => $toum->id, 'qty' => 30, 'unit' => 'g']);

        $this->assertSame('Garlic sauce (toum)', $sandwich->lines->first()->subRecipe->name);
        $this->assertSame('30.000', RecipeLine::first()->qty);
    }

    public function test_supplier_with_prices_cannot_be_deleted_at_database_level(): void
    {
        $supplier = Supplier::create(['name' => 'Hamra Poultry']);
        $chicken = Ingredient::create(['name' => 'Chicken breast', 'base_unit' => 'g']);
        IngredientPrice::create([
            'ingredient_id' => $chicken->id,
            'supplier_id' => $supplier->id,
            'price' => 5,
            'unit' => 'kg',
            'price_per_base_unit' => 0.005,
            'effective_from' => '2026-11-01',
        ]);

        $this->expectException(QueryException::class);
        $supplier->delete();
    }
}
