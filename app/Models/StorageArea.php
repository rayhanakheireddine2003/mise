<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A place where ingredients are kept, e.g. "Cold room". */
#[Fillable(['name'])]
class StorageArea extends Model
{
    public function ingredients(): HasMany
    {
        return $this->hasMany(Ingredient::class);
    }
}
