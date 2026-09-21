<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'google_subject',
        'active',
        'is_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'google_subject',
    ];

    protected $attributes = [
        'active' => true,
        'is_admin' => false,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
            'is_admin' => 'boolean',
        ];
    }
}
