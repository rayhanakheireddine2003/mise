<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Something the kitchen buys, e.g. "Chicken breast".
 * All its quantities are stored in its base unit (g, ml or piece).
 */
#[Fillable(['name', 'base_unit', 'grams_per_piece', 'storage_area_id'])]
class Ingredient extends Model
{
    /** The only allowed values for base_unit. */
    public const BASE_UNITS = ['g', 'ml', 'piece'];

    protected function casts(): array
    {
        return [
            'grams_per_piece' => 'decimal:3',
        ];
    }

    public function storageArea(): BelongsTo
    {
        return $this->belongsTo(StorageArea::class);
    }

    /** Pack sizes such as "tin = 4000 ml". */
    public function units(): HasMany
    {
        return $this->hasMany(IngredientUnit::class);
    }

    /** Full price history (never edited, only added to). */
    public function prices(): HasMany
    {
        return $this->hasMany(IngredientPrice::class);
    }
}
