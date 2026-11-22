<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One price, valid from effective_from until a newer price exists.
 * Rule: never update or delete a price. Create a new row instead.
 */
#[Fillable(['ingredient_id', 'supplier_id', 'price', 'unit', 'price_per_base_unit', 'effective_from'])]
class IngredientPrice extends Model
{
    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'price' => 'decimal:4',
            'price_per_base_unit' => 'decimal:6',
        ];
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
