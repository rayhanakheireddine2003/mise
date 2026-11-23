<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\User;
use App\Services\PriceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** MISE-11: the demo data seeder (php artisan migrate:fresh --seed). */
class DemoDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_is_complete_and_can_run_twice(): void
    {
        $this->seed();
        $this->seed(); // running it again must not create duplicates

        $this->assertSame(['chef', 'owner', 'storekeeper'], User::orderBy('role')->pluck('role')->all());
        $this->assertSame(8, Ingredient::count());
        $this->assertSame(3, Recipe::count());

        // Chicken: $5/kg until the 15th, $6/kg from the 16th.
        $chicken = Ingredient::firstWhere('name', 'Chicken breast');
        $this->assertSame(2, $chicken->prices()->count());
        $this->assertEquals(5.00, (new PriceService)->priceAt($chicken, '2026-11-10')->price);
        $this->assertEquals(6.00, (new PriceService)->priceAt($chicken, '2026-11-20')->price);

        // Garlic sauce is a sub-recipe used inside both dishes.
        $toum = Recipe::firstWhere('name', 'Garlic sauce (toum)');
        $this->assertTrue($toum->is_sub_recipe);
        foreach (['Shish taouk platter', 'Taouk sandwich'] as $dish) {
            $this->assertTrue(Recipe::firstWhere('name', $dish)->lines()->where('sub_recipe_id', $toum->id)->exists());
        }
    }

    public function test_each_demo_user_can_log_in_and_sees_their_own_menu(): void
    {
        $this->seed();

        $this->post('/login', ['email' => 'storekeeper@mise.test', 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->get(route('dashboard'))->assertSee('Suppliers')->assertDontSee('Storage areas');
        $this->get('/ingredients')->assertForbidden();
    }
}
