<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class IngredientPurchase extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        // Strings preserve exactness across JSON, including values above JS's safe integer limit.
        return array_fill_keys([
            'total_paid_minor', 'purchase_quantity_milli',
            'normalized_quantity_milli', 'normalized_unit_cost_micros',
        ], 'string');
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Las compras son inmutables.'));
        static::deleting(fn () => throw new LogicException('Las compras son inmutables.'));
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }
}
