<?php

namespace Tests\Feature;

use App\Models\User;
use App\View\MenuLinks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** MISE-3: each role only sees its own menu items. */
class MenuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Make sure every menu page "exists" for this test, even before it is merged.
        foreach (['recipes.index', 'ingredients.index', 'storage-areas.index', 'suppliers.index', 'prices.overview'] as $name) {
            if (! Route::has($name)) {
                Route::get('/_test/'.$name, fn () => 'ok')->name($name);
            }
        }
        Route::getRoutes()->refreshNameLookups();
    }

    private function labelsFor(string $role): array
    {
        return array_column(MenuLinks::for(User::factory()->make(['role' => $role])), 'label');
    }

    public function test_each_role_sees_only_its_own_menu_items(): void
    {
        $this->assertSame(['Recipes', 'Ingredients', 'Storage areas'], $this->labelsFor('chef'));
        $this->assertSame(['Suppliers', 'Prices'], $this->labelsFor('storekeeper'));
        $this->assertSame(['Recipes', 'Ingredients', 'Storage areas', 'Suppliers', 'Prices'], $this->labelsFor('owner'));
    }

    public function test_menu_is_shown_on_the_dashboard(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'storekeeper']))
            ->get(route('dashboard'))
            ->assertSee('Suppliers')
            ->assertDontSee('Ingredients');
    }
}
