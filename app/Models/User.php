<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    // 'role' is intentionally NOT mass-assignable from public forms (mass-assignment protection);
    // it is set explicitly by trusted code (registration = customer, admin user management).
    protected $fillable = ['name', 'email', 'username', 'password', 'company'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => Role::class,
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::ADMIN;
    }

    public function isOperator(): bool
    {
        return $this->role === Role::OPERATOR;
    }

    public function isCustomer(): bool
    {
        return $this->role === Role::CUSTOMER;
    }

    public function isStaff(): bool
    {
        return $this->isAdmin() || $this->isOperator();
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'customer_id');
    }

    public function appNotifications(): HasMany
    {
        return $this->hasMany(AppNotification::class);
    }

    public function initials(): string
    {
        return collect(explode(' ', trim($this->name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
    }
}
