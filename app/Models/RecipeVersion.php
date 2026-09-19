<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class RecipeVersion extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return array_fill_keys([
            'expected_yield', 'snapshot_batch_cost_micros', 'snapshot_unit_cost_micros',
        ], 'string');
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Las versiones de receta son inmutables.'));
        static::deleting(fn () => throw new LogicException('Las versiones de receta son inmutables.'));
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(RecipeIngredientLine::class)->orderBy('position');
    }
}
