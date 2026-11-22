<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A dish on the menu, or a sub-recipe (is_sub_recipe = true) such as
 * garlic sauce that is used as a line inside other recipes.
 */
#[Fillable(['name', 'is_sub_recipe', 'yield_qty', 'yield_unit', 'waste_pct', 'menu_price', 'target_cost_pct'])]
class Recipe extends Model
{
    protected function casts(): array
    {
        return [
            'is_sub_recipe' => 'boolean',
            'yield_qty' => 'decimal:3',
            'waste_pct' => 'decimal:2',
            'menu_price' => 'decimal:2',
            'target_cost_pct' => 'decimal:2',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(RecipeLine::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function marginAlerts(): HasMany
    {
        return $this->hasMany(MarginAlert::class);
    }
}
