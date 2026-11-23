<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PriceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceTest extends TestCase
{
    use RefreshDatabase;

    /** Adds a price for $ingredient per kg, starting on $from. */
    private function addPrice(Ingredient $ingredient, float $perKg, string $from): void
    {
        $supplier = Supplier::firstOrCreate(['name' => 'Hamra Poultry']);

        $ingredient->prices()->create([
            'supplier_id' => $supplier->id,
            'price' => $perKg,
            'unit' => 'kg',
            'price_per_base_unit' => $perKg / 1000,
            'effective_from' => $from,
        ]);
    }

    public function test_price_at_returns_the_price_in_effect_on_each_date(): void
    {
        $chicken = Ingredient::create(['name' => 'Chicken breast', 'base_unit' => 'g']);
        $this->addPrice($chicken, 5.00, '2026-11-01');
        $this->addPrice($chicken, 6.00, '2026-11-16');
        $service = new PriceService;

        // Before any price: nothing.
        $this->assertNull($service->priceAt($chicken, '2026-10-31'));
        // From the 1st to the 15th: $5.
        $this->assertEquals(5.00, $service->priceAt($chicken, '2026-11-01')->price);
        $this->assertEquals(5.00, $service->priceAt($chicken, '2026-11-15')->price);
        // From the 16th: $6.
        $this->assertEquals(6.00, $service->priceAt($chicken, '2026-11-16')->price);
        $this->assertEquals(6.00, $service->priceAt($chicken, '2026-12-31')->price);
    }

    public function test_two_prices_on_the_same_day_the_last_one_entered_wins(): void
    {
        $chicken = Ingredient::create(['name' => 'Chicken breast', 'base_unit' => 'g']);
        $this->addPrice($chicken, 5.00, '2026-11-16');
        $this->addPrice($chicken, 5.50, '2026-11-16'); // a correction entered later

        $this->assertEquals(5.50, (new PriceService)->priceAt($chicken, '2026-11-20')->price);
    }

    public function test_price_at_ignores_other_ingredients(): void
    {
        $chicken = Ingredient::create(['name' => 'Chicken breast', 'base_unit' => 'g']);
        $garlic = Ingredient::create(['name' => 'Garlic', 'base_unit' => 'g']);
        $this->addPrice($garlic, 3.00, '2026-11-01');

        $this->assertNull((new PriceService)->priceAt($chicken, '2026-11-20'));
    }

    // ----- MISE-9: the price pages -----

    public function test_storekeeper_records_a_new_price_and_the_old_one_stays(): void
    {
        $storekeeper = User::factory()->create(['role' => 'storekeeper']);
        $chicken = Ingredient::create(['name' => 'Chicken breast', 'base_unit' => 'g']);
        $this->addPrice($chicken, 5.00, '2026-11-01');
        $supplier = Supplier::first();

        $this->actingAs($storekeeper)
            ->post(route('ingredients.prices.store', $chicken), [
                'supplier_id' => $supplier->id, 'price' => 6, 'unit' => 'kg', 'effective_from' => '2026-11-16',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, $chicken->prices()->count()); // the $5 price is still there
        $this->assertEquals(0.006, (new PriceService)->priceAt($chicken, '2026-11-20')->price_per_base_unit);
        $this->actingAs($storekeeper)->get(route('ingredients.prices.index', $chicken))
            ->assertSee('$5.00 / kg')->assertSee('$6.00 / kg');
    }

    public function test_chicken_per_ml_shows_an_error_instead_of_saving(): void
    {
        $chicken = Ingredient::create(['name' => 'Chicken breast', 'base_unit' => 'g']);
        $supplier = Supplier::create(['name' => 'Hamra Poultry']);

        $this->actingAs(User::factory()->create(['role' => 'storekeeper']))
            ->post(route('ingredients.prices.store', $chicken), [
                'supplier_id' => $supplier->id, 'price' => 6, 'unit' => 'ml', 'effective_from' => '2026-11-16',
            ])
            ->assertSessionHasErrors('unit');

        $this->assertSame(0, $chicken->prices()->count());
    }

    public function test_price_per_pack_is_turned_into_price_per_base_unit(): void
    {
        $oil = Ingredient::create(['name' => 'Vegetable oil', 'base_unit' => 'ml']);
        $oil->units()->create(['name' => 'tin', 'qty_in_base_unit' => 4000]);
        $supplier = Supplier::create(['name' => 'Central Market']);

        $price = (new PriceService)->record($oil, $supplier->id, 20.00, 'tin', '2026-11-01');

        $this->assertEquals(0.005, $price->price_per_base_unit); // $20 / 4000 ml
        $this->assertSame('$5.00 / l', $price->perStandardUnitLabel('ml'));
    }
}
