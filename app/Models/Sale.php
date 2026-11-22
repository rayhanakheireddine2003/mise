<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** How many portions of a recipe were sold on a day. */
#[Fillable(['recipe_id', 'sold_on', 'qty'])]
class Sale extends Model
{
    protected function casts(): array
    {
        return [
            'sold_on' => 'date',
            'qty' => 'decimal:2',
        ];
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}
