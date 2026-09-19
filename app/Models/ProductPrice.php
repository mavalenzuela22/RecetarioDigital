<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class ProductPrice extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return ['price_minor' => 'string', 'effective_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Los precios históricos son inmutables.'));
        static::deleting(fn () => throw new LogicException('Los precios históricos son inmutables.'));
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
