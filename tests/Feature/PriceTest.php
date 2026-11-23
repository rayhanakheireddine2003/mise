<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\Supplier;
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
}
