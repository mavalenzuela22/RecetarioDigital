<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class ProductCostProfile extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return array_fill_keys(['version_number', 'reference_order_quantity'], 'string');
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Los perfiles de costos son inmutables.'));
        static::deleting(fn () => throw new LogicException('Los perfiles de costos son inmutables.'));
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(ProductCostComponent::class)->orderBy('position');
    }
}
