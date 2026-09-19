<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LogicException;

class Ingredient extends Model
{
    protected $fillable = ['name', 'name_key', 'canonical_unit'];

    protected static function booted(): void
    {
        static::updating(function (Ingredient $ingredient): void {
            if ($ingredient->isDirty('canonical_unit')) {
                throw new LogicException('La unidad canónica de un ingrediente no puede cambiar.');
            }
        });
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(IngredientPurchase::class)->orderByDesc('purchased_on')->orderByDesc('id');
    }

    public function currentPurchase(): HasOne
    {
        return $this->hasOne(IngredientPurchase::class)->ofMany(['purchased_on' => 'max', 'id' => 'max']);
    }
}
