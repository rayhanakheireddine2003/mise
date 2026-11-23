<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line of a recipe: either an ingredient OR a sub-recipe, never both.
 * This is enforced in booted() below, so it holds everywhere: forms,
 * the seeder and tests (MISE-10).
 */
#[Fillable(['recipe_id', 'ingredient_id', 'sub_recipe_id', 'qty', 'unit'])]
class RecipeLine extends Model
{
    /**
     * Runs automatically every time a line is created or updated.
     * Throwing an exception stops the save, so a bad line never reaches the database.
     */
    protected static function booted(): void
    {
        static::saving(function (RecipeLine $line) {
            $hasIngredient = ! empty($line->ingredient_id);
            $hasSubRecipe = ! empty($line->sub_recipe_id);

            // Exactly one of the two: not both, not neither.
            if ($hasIngredient && $hasSubRecipe) {
                throw new DomainException('A recipe line can use an ingredient or a sub-recipe, not both.');
            }
            if (! $hasIngredient && ! $hasSubRecipe) {
                throw new DomainException('A recipe line needs an ingredient or a sub-recipe.');
            }

            // A recipe can't contain itself (A inside A).
            // Longer loops (A inside B inside A) are checked in Sprint 2 with costing.
            if ($hasSubRecipe && (int) $line->sub_recipe_id === (int) $line->recipe_id) {
                throw new DomainException('A recipe cannot contain itself.');
            }
        });
    }

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
