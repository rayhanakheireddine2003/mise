<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** How much of one ingredient was found during a stock count (in base unit). */
#[Fillable(['stock_count_id', 'ingredient_id', 'qty_in_base_unit'])]
class CountLine extends Model
{
    protected function casts(): array
    {
        return [
            'qty_in_base_unit' => 'decimal:3',
        ];
    }

    public function stockCount(): BelongsTo
    {
        return $this->belongsTo(StockCount::class);
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }
}
