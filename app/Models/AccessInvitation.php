<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class AccessInvitation extends Model
{
    protected $fillable = [
        'email',
        'token_hash',
        'created_by',
        'expires_at',
        'accepted_at',
        'accepted_by',
        'revoked_at',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    public function isUsable(): bool
    {
        return $this->accepted_at === null
            && $this->revoked_at === null
            && $this->expires_at?->isFuture() === true;
    }

    public function status(): string
    {
        return match (true) {
            $this->revoked_at !== null => 'Revocada',
            $this->accepted_at !== null => 'Aceptada',
            $this->expires_at?->isPast() => 'Expirada',
            default => 'Pendiente',
        };
    }
}
