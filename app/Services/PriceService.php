<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\IngredientPrice;

/**
 * MISE-8: finds which price was in effect on a given day.
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
}
