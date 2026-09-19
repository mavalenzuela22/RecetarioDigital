<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class OrderLine extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return array_fill_keys([
            'quantity', 'agreed_unit_price_minor', 'line_revenue_minor',
            'attributable_unit_cost_micros', 'attributable_line_cost_micros',
        ], 'string') + ['cost_complete' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Las líneas de pedido son inmutables.'));
        static::deleting(fn () => throw new LogicException('Las líneas de pedido son inmutables.'));
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productPrice(): BelongsTo
    {
        return $this->belongsTo(ProductPrice::class);
    }

    public function recipeVersion(): BelongsTo
    {
        return $this->belongsTo(RecipeVersion::class);
    }

    public function productCostProfile(): BelongsTo
    {
        return $this->belongsTo(ProductCostProfile::class);
    }
}
