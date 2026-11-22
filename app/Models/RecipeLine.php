<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line of a recipe: either an ingredient OR a sub-recipe, never both.
 * The rule that enforces this (RecipeLine::saving) is added in MISE-10.
 */
#[Fillable(['recipe_id', 'ingredient_id', 'sub_recipe_id', 'qty', 'unit'])]
class RecipeLine extends Model
{
    protected function casts(): array
    {
        return [
            'qty' => 'decimal:3',
        ];
    }

    /** The recipe this line belongs to. */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    /** The recipe used as an ingredient (e.g. garlic sauce inside a sandwich). */
    public function subRecipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class, 'sub_recipe_id');
    }
}
