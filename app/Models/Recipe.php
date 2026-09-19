<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Recipe extends Model
{
    protected $fillable = ['name'];

    public function versions(): HasMany
    {
        return $this->hasMany(RecipeVersion::class)->orderByDesc('version_number');
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(RecipeVersion::class)->latestOfMany('version_number');
    }
}
