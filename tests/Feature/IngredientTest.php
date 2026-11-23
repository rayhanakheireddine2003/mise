<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\StorageArea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IngredientTest extends TestCase
{
    use RefreshDatabase;

    private function chef(): User
    {
        return User::factory()->create(['role' => 'chef']);
    }

    public function test_chef_can_add_an_ingredient(): void
    {
        $area = StorageArea::create(['name' => 'Cold room']);

        $this->actingAs($this->chef())->post(route('ingredients.store'), [
            'name' => 'Chicken breast',
            'base_unit' => 'g',
            'storage_area_id' => $area->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('ingredients', [
            'name' => 'Chicken breast', 'base_unit' => 'g', 'storage_area_id' => $area->id,
        ]);
    }

    public function test_duplicate_name_shows_an_error(): void
    {
        Ingredient::create(['name' => 'Garlic', 'base_unit' => 'g']);

        $this->actingAs($this->chef())
            ->post(route('ingredients.store'), ['name' => 'Garlic', 'base_unit' => 'g'])
            ->assertSessionHasErrors('name');

        $this->assertSame(1, Ingredient::count());
    }

    public function test_base_unit_must_be_g_ml_or_piece(): void
    {
        $this->actingAs($this->chef())
            ->post(route('ingredients.store'), ['name' => 'Flour', 'base_unit' => 'kg'])
            ->assertSessionHasErrors('base_unit');
    }

    public function test_editing_keeps_the_same_name_without_a_duplicate_error(): void
    {
        $lemon = Ingredient::create(['name' => 'Lemon', 'base_unit' => 'piece']);

        $this->actingAs($this->chef())
            ->put(route('ingredients.update', $lemon), ['name' => 'Lemon', 'base_unit' => 'piece', 'grams_per_piece' => 120])
            ->assertSessionHasNoErrors();

        $this->assertEquals(120, $lemon->fresh()->grams_per_piece);
    }

    public function test_search_and_area_filter(): void
    {
        $cold = StorageArea::create(['name' => 'Cold room']);
        Ingredient::create(['name' => 'Chicken breast', 'base_unit' => 'g', 'storage_area_id' => $cold->id]);
        Ingredient::create(['name' => 'Chickpeas', 'base_unit' => 'g']);
        Ingredient::create(['name' => 'Salt', 'base_unit' => 'g']);

        $this->actingAs($this->chef())
            ->get(route('ingredients.index', ['q' => 'chick', 'area' => $cold->id]))
            ->assertSee('Chicken breast')
            ->assertDontSee('Chickpeas')
            ->assertDontSee('Salt');
    }

    public function test_ingredient_used_in_a_recipe_cannot_be_deleted(): void
    {
        $garlic = Ingredient::create(['name' => 'Garlic', 'base_unit' => 'g']);
        $toum = Recipe::create(['name' => 'Garlic sauce (toum)', 'is_sub_recipe' => true]);
        $toum->lines()->create(['ingredient_id' => $garlic->id, 'qty' => 200, 'unit' => 'g']);

        $this->actingAs($this->chef())
            ->delete(route('ingredients.destroy', $garlic))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('ingredients', ['id' => $garlic->id]);
    }

    // ----- Pack sizes (MISE-6) -----

    public function test_chef_can_add_a_pack_size_and_it_shows_on_the_edit_page(): void
    {
        $chef = $this->chef();
        $oil = Ingredient::create(['name' => 'Vegetable oil', 'base_unit' => 'ml']);

        $this->actingAs($chef)
            ->post(route('ingredients.units.store', $oil), ['pack_name' => 'tin', 'pack_qty' => 4000])
            ->assertRedirect(route('ingredients.edit', $oil));

        $this->assertDatabaseHas('ingredient_units', ['ingredient_id' => $oil->id, 'name' => 'tin', 'qty_in_base_unit' => 4000]);
        $this->actingAs($chef)->get(route('ingredients.edit', $oil))->assertSee('tin');
    }

    public function test_pack_size_name_must_be_unique_per_ingredient_and_not_a_standard_unit(): void
    {
        $fries = Ingredient::create(['name' => 'Frozen fries', 'base_unit' => 'g']);
        $fries->units()->create(['name' => 'bag', 'qty_in_base_unit' => 2500]);

        $this->actingAs($this->chef())
            ->post(route('ingredients.units.store', $fries), ['pack_name' => 'bag', 'pack_qty' => 1000])
            ->assertSessionHasErrors('pack_name');

        $this->actingAs($this->chef())
            ->post(route('ingredients.units.store', $fries), ['pack_name' => 'kg', 'pack_qty' => 1000])
            ->assertSessionHasErrors('pack_name');

        $this->assertSame(1, $fries->units()->count());
    }

    public function test_pack_size_quantity_must_be_positive_and_pack_can_be_deleted(): void
    {
        $bread = Ingredient::create(['name' => 'Saj bread', 'base_unit' => 'piece']);

        $this->actingAs($this->chef())
            ->post(route('ingredients.units.store', $bread), ['pack_name' => 'pack', 'pack_qty' => 0])
            ->assertSessionHasErrors('pack_qty');

        $pack = $bread->units()->create(['name' => 'pack', 'qty_in_base_unit' => 10]);
        $this->actingAs($this->chef())->delete(route('ingredient-units.destroy', $pack));

        $this->assertSame(0, $bread->units()->count());
    }
}
