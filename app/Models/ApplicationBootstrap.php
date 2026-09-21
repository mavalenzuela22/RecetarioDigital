<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationBootstrap extends Model
{
    protected $table = 'application_bootstrap';

    protected $fillable = [
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    public static function isComplete(): bool
    {
        return static::query()->whereKey(1)->value('completed_at') !== null;
    }
}
