<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return array_fill_keys(['total_minor', 'paid_minor', 'balance_minor'], 'string');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(OrderLine::class)->orderBy('id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class)->orderBy('effective_at')->orderBy('id');
    }
}
