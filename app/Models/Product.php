<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    protected $fillable = ['recipe_id', 'sale_unit', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function costProfiles(): HasMany
    {
        return $this->hasMany(ProductCostProfile::class)->orderByDesc('version_number');
    }

    public function latestProfile(): HasOne
    {
        return $this->hasOne(ProductCostProfile::class)->latestOfMany('version_number');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class)->orderByDesc('effective_at')->orderByDesc('id');
    }

    public function latestPrice(): HasOne
    {
        return $this->hasOne(ProductPrice::class)->ofMany(['effective_at' => 'max', 'id' => 'max']);
    }
}
