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
}
