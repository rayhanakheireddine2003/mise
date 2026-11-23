<?php

namespace App\Services;

use App\Models\Ingredient;
use InvalidArgumentException;

/**
 * MISE-2: converts quantities to and from an ingredient's base unit.
 *
 * Every quantity in the database is stored in the ingredient's base unit
 * (g, ml or piece). Convert only when reading a form or showing a number.
 *
 * A unit can be:
 *  - a pack size of that ingredient, e.g. "tin" = 4000 ml (checked first), or
 *  - a standard unit: g, kg, ml, l, piece.
 *
 * Pieces <-> grams only works when the ingredient has grams_per_piece
 * (e.g. 1 lemon = 120 g). Grams <-> ml is never allowed.
 */
class UnitConverter
{
    /** unit => [what it measures, how many of the smallest unit it holds] */
    public const STANDARD_UNITS = [
        'g' => ['dimension' => 'mass', 'factor' => 1],
        'kg' => ['dimension' => 'mass', 'factor' => 1000],
        'ml' => ['dimension' => 'volume', 'factor' => 1],
        'l' => ['dimension' => 'volume', 'factor' => 1000],
        'piece' => ['dimension' => 'count', 'factor' => 1],
    ];

    /**
     * How many base units $qty $unit is.
     * Example: toBase(chicken, 2, 'kg') = 2000 (grams).
     *
     * @throws InvalidArgumentException for negative quantities, unknown units
     *                                  and impossible conversions (e.g. kg -> ml)
     */
    public function toBase(Ingredient $ingredient, float $qty, string $unit): float
    {
        if ($qty < 0) {
            throw new InvalidArgumentException('Quantity cannot be negative.');
        }

        // 1. A pack size of this ingredient? ("tin", "case", "bag"...)
        $pack = $ingredient->units->firstWhere('name', $unit);
        if ($pack) {
            return $qty * (float) $pack->qty_in_base_unit;
        }

        // 2. A standard unit?
        if (! isset(self::STANDARD_UNITS[$unit])) {
            throw new InvalidArgumentException("Unknown unit \"{$unit}\" for {$ingredient->name}.");
        }

        $from = self::STANDARD_UNITS[$unit];
        $base = self::STANDARD_UNITS[$ingredient->base_unit];
        $amount = $qty * $from['factor']; // now in g, ml or piece

        // Same kind of measure (kg -> g, l -> ml, piece -> piece): done.
        if ($from['dimension'] === $base['dimension']) {
            return $amount;
        }

        // Pieces <-> grams needs to know how heavy one piece is.
        $gramsPerPiece = (float) $ingredient->grams_per_piece;
        if ($gramsPerPiece > 0) {
            if ($from['dimension'] === 'mass' && $base['dimension'] === 'count') {
                return $amount / $gramsPerPiece; // grams -> pieces
            }
            if ($from['dimension'] === 'count' && $base['dimension'] === 'mass') {
                return $amount * $gramsPerPiece; // pieces -> grams
            }
        }

        throw new InvalidArgumentException(
            "Cannot convert {$unit} to {$ingredient->base_unit} for {$ingredient->name}."
        );
    }

    /**
     * Converts between any two units of the same ingredient.
     * Example: convert(oil, 2, 'tin', 'l') = 8.
     */
    public function convert(Ingredient $ingredient, float $qty, string $from, string $to): float
    {
        $inBase = $this->toBase($ingredient, $qty, $from);
        $oneTarget = $this->toBase($ingredient, 1, $to); // base units in 1 "$to"

        return $inBase / $oneTarget;
    }

    /**
     * Units that make sense for this ingredient, for <select> dropdowns:
     * its pack sizes first, then the standard units it can be converted from.
     *
     * @return string[]
     */
    public function unitsFor(Ingredient $ingredient): array
    {
        $units = $ingredient->units->pluck('name')->all();

        foreach (array_keys(self::STANDARD_UNITS) as $unit) {
            try {
                $this->toBase($ingredient, 1, $unit);
                $units[] = $unit;
            } catch (InvalidArgumentException) {
                // not convertible for this ingredient: leave it out
            }
        }

        return array_values(array_unique($units));
    }
}
