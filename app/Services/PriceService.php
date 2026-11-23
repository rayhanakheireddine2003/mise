<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\IngredientPrice;
use InvalidArgumentException;

/**
 * MISE-8: finds which price was in effect on a given day.
 * MISE-9: records a new price (never edits an old one).
 *
 * Prices are never edited (architecture rule 3). Each change is a new row
 * with an effective_from date, so "the price on day X" is the newest row
 * that started on or before day X.
 */
class PriceService
{
    /**
     * The price in effect on $date (format YYYY-MM-DD), or null if the
     * ingredient had no price yet on that day.
     *
     * If two prices start on the same day, the one entered last (highest id) wins.
     */
    public function priceAt(Ingredient $ingredient, string $date): ?IngredientPrice
    {
        return $ingredient->prices()
            ->whereDate('effective_from', '<=', $date)
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();
    }

    public function __construct(private UnitConverter $converter = new UnitConverter)
    {
    }

    /**
     * Saves a NEW price row. Old prices are never changed, so history stays.
     *
     * Also works out price_per_base_unit: what 1 g / 1 ml / 1 piece costs.
     * Example: chicken $6.00 per kg -> 6 / 1000 g = $0.006 per g.
     *
     * @throws InvalidArgumentException if the unit doesn't fit the ingredient
     *                                  (e.g. chicken "per ml")
     */
    public function record(Ingredient $ingredient, int $supplierId, float $price, string $unit, string $effectiveFrom): IngredientPrice
    {
        if ($price <= 0) {
            throw new InvalidArgumentException('The price must be more than 0.');
        }

        // How many base units are in ONE of the unit the supplier sells by.
        $baseUnitsInOne = $this->converter->toBase($ingredient, 1, $unit);
        if ($baseUnitsInOne <= 0) {
            throw new InvalidArgumentException("\"{$unit}\" contains nothing, so it can't have a price.");
        }

        return $ingredient->prices()->create([
            'supplier_id' => $supplierId,
            'price' => $price,
            'unit' => $unit,
            'price_per_base_unit' => $price / $baseUnitsInOne,
            'effective_from' => $effectiveFrom,
        ]);
    }
}
