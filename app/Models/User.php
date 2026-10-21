<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'employee_id',
        'username',
        'email',
        'email_verified_at',
        'password',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, fn ($q) => $q->where(fn ($q) => $q->where('username', 'like', "%{$term}%")
            ->orWhere('email', 'like', "%{$term}%")
        )
        );
    }

    public function scopeByRole(Builder $query, ?string $role): Builder
    {
        return $query->when($role, fn ($q) => $q->role($role));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->hasRole('coordinador_oti') || $this->hasRole('jefe_oti') || $this->hasRole('admin');
    }

    public function isCoordinador(): bool
    {
        return $this->hasRole('coordinador_oti') || $this->hasRole('admin');
    }

    public function isJefe(): bool
    {
        return $this->hasRole('jefe_oti');
    }

    public function isTecnico(): bool
    {
        return $this->hasRole('tecnico');
    }

    public function isAuxiliar(): bool
    {
        return $this->hasRole('auxiliar_ti');
    }

    public function isPracticante(): bool
    {
        return $this->isAuxiliar();
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
