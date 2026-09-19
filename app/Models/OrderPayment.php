<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class OrderPayment extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return ['amount_minor' => 'string', 'effective_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Los pagos históricos son inmutables.'));
        static::deleting(fn () => throw new LogicException('Los pagos históricos son inmutables.'));
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
