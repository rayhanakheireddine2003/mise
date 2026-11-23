<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeLine;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * MISE-10: a recipe line has exactly one of ingredient_id or sub_recipe_id,
 * and a recipe can't contain itself.
 */
class RecipeLineTest extends TestCase
{
    use RefreshDatabase;

    private Recipe $sandwich;
    private Recipe $toum;
    private Ingredient $chicken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->chicken = Ingredient::create(['name' => 'Chicken breast', 'base_unit' => 'g']);
        $this->toum = Recipe::create(['name' => 'Garlic sauce (toum)', 'is_sub_recipe' => true, 'yield_qty' => 1000, 'yield_unit' => 'g']);
        $this->sandwich = Recipe::create(['name' => 'Taouk sandwich', 'menu_price' => 5]);
    }

    public function test_line_with_only_an_ingredient_is_saved(): void
    {
        $this->sandwich->lines()->create(['ingredient_id' => $this->chicken->id, 'qty' => 150, 'unit' => 'g']);

        $this->assertSame(1, RecipeLine::count());
    }

    public function test_line_with_only_a_sub_recipe_is_saved(): void
    {
        $this->sandwich->lines()->create(['sub_recipe_id' => $this->toum->id, 'qty' => 30, 'unit' => 'g']);

        $this->assertSame(1, RecipeLine::count());
    }

    public function test_line_with_both_is_refused(): void
    {
        $this->expectException(DomainException::class);

        $this->sandwich->lines()->create([
            'ingredient_id' => $this->chicken->id,
            'sub_recipe_id' => $this->toum->id,
            'qty' => 30,
            'unit' => 'g',
        ]);
    }

    public function test_line_with_neither_is_refused(): void
    {
        $this->expectException(DomainException::class);

        $this->sandwich->lines()->create(['qty' => 30, 'unit' => 'g']);
    }

    public function test_recipe_cannot_contain_itself_even_when_editing_a_line(): void
    {
        // Creating a line pointing to itself is refused...
        try {
            $this->toum->lines()->create(['sub_recipe_id' => $this->toum->id, 'qty' => 10, 'unit' => 'g']);
            $this->fail('A recipe was allowed to contain itself.');
        } catch (DomainException) {
            // expected
        }

        // ...and so is changing an existing, valid line to point to itself.
        $line = $this->toum->lines()->create(['ingredient_id' => $this->chicken->id, 'qty' => 10, 'unit' => 'g']);
        $this->expectException(DomainException::class);
        $line->update(['ingredient_id' => null, 'sub_recipe_id' => $this->toum->id]);
    }
}
