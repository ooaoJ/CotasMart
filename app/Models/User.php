<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'plan', 'subscription_status',
        'trial_ends_at', 'subscription_ends_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function hasAccess(): bool
    {
        if ($this->isAdmin()) return true;
        if ($this->subscription_status === 'active' && (! $this->subscription_ends_at || $this->subscription_ends_at->isFuture())) return true;
        return $this->subscription_status === 'trial' && $this->trial_ends_at?->isFuture();
    }
}

