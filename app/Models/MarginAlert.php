<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Raised when a dish's food-cost % goes above its target after a price change. */
#[Fillable(['recipe_id', 'old_cost_pct', 'new_cost_pct', 'seen'])]
class MarginAlert extends Model
{
    protected function casts(): array
    {
        return [
            'old_cost_pct' => 'decimal:2',
            'new_cost_pct' => 'decimal:2',
            'seen' => 'boolean',
        ];
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}
