<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A stock count on a given day. status is "draft" or "done". */
#[Fillable(['counted_on', 'status', 'user_id'])]
class StockCount extends Model
{
    public const STATUSES = ['draft', 'done'];

    protected function casts(): array
    {
        return [
            'counted_on' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(CountLine::class);
    }
}
