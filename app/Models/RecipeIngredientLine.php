<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class RecipeIngredientLine extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return array_fill_keys([
            'quantity_milli', 'normalized_quantity_milli', 'snapshot_unit_cost_micros',
            'snapshot_usage_cost_micros',
        ], 'string');
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Las líneas de receta son inmutables.'));
        static::deleting(fn () => throw new LogicException('Las líneas de receta son inmutables.'));
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(RecipeVersion::class, 'recipe_version_id');
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function snapshotPurchase(): BelongsTo
    {
        return $this->belongsTo(IngredientPurchase::class, 'snapshot_purchase_id');
    }
}
