<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A pack size for one ingredient, e.g. "bag" = 2500 g of frozen fries. */
#[Fillable(['ingredient_id', 'name', 'qty_in_base_unit'])]
class IngredientUnit extends Model
{
    protected function casts(): array
    {
        return [
            'qty_in_base_unit' => 'decimal:3',
        ];
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }
}
