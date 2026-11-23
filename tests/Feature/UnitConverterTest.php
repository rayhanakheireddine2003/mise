<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Services\UnitConverter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

/** MISE-3: App\Services\UnitConverter from MISE-2. */
class UnitConverterTest extends TestCase
{
    use RefreshDatabase;

    private UnitConverter $converter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->converter = new UnitConverter;
    }

    public function test_kg_to_grams(): void
    {
        $chicken = Ingredient::create(['name' => 'Chicken breast', 'base_unit' => 'g']);

        $this->assertEquals(2500, $this->converter->toBase($chicken, 2.5, 'kg'));
    }

    public function test_litres_to_ml(): void
    {
        $oil = Ingredient::create(['name' => 'Vegetable oil', 'base_unit' => 'ml']);

        $this->assertEquals(1500, $this->converter->toBase($oil, 1.5, 'l'));
    }

    public function test_pack_size_is_used(): void
    {
        $oil = Ingredient::create(['name' => 'Vegetable oil', 'base_unit' => 'ml']);
        $oil->units()->create(['name' => 'tin', 'qty_in_base_unit' => 4000]);

        $this->assertEquals(8000, $this->converter->toBase($oil, 2, 'tin'));
        $this->assertEquals(8, $this->converter->convert($oil, 2, 'tin', 'l'));
    }

    public function test_pieces_and_grams_with_grams_per_piece(): void
    {
        $lemon = Ingredient::create(['name' => 'Lemon', 'base_unit' => 'piece', 'grams_per_piece' => 120]);

        $this->assertEquals(10, $this->converter->toBase($lemon, 1.2, 'kg'));
        $this->assertEquals(360, $this->converter->convert($lemon, 3, 'piece', 'g'));
    }

    public function test_pieces_to_grams_without_grams_per_piece_is_refused(): void
    {
        $bread = Ingredient::create(['name' => 'Saj bread', 'base_unit' => 'piece']);

        $this->expectException(InvalidArgumentException::class);
        $this->converter->toBase($bread, 1, 'kg');
    }

    public function test_mass_to_volume_is_refused(): void
    {
        $chicken = Ingredient::create(['name' => 'Chicken breast', 'base_unit' => 'g']);

        $this->expectException(InvalidArgumentException::class);
        $this->converter->toBase($chicken, 1, 'ml');
    }

    public function test_unknown_unit_and_negative_quantity_are_refused(): void
    {
        $salt = Ingredient::create(['name' => 'Salt', 'base_unit' => 'g']);

        try {
            $this->converter->toBase($salt, 1, 'spoon');
            $this->fail('An unknown unit was accepted.');
        } catch (InvalidArgumentException) {
            // expected
        }

        $this->expectException(InvalidArgumentException::class);
        $this->converter->toBase($salt, -1, 'g');
    }

    public function test_units_for_lists_pack_sizes_then_compatible_units(): void
    {
        $fries = Ingredient::create(['name' => 'Frozen fries', 'base_unit' => 'g']);
        $fries->units()->create(['name' => 'bag', 'qty_in_base_unit' => 2500]);

        $this->assertSame(['bag', 'g', 'kg'], $this->converter->unitsFor($fries));
    }
}
