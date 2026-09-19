<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class ProductCostComponent extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return array_fill_keys(['position', 'amount_minor'], 'string');
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Los componentes de costos son inmutables.'));
        static::deleting(fn () => throw new LogicException('Los componentes de costos son inmutables.'));
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(ProductCostProfile::class, 'product_cost_profile_id');
    }
}
