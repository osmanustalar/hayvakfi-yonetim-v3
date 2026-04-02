<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\LogsActivity;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, HasRoles, LogsActivity, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'password',
        'can_login',
        'is_active',
        'default_company_id',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'can_login' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->can_login && $this->is_active;
    }

    public function defaultCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'default_company_id');
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_user');
    }

    public function safes(): BelongsToMany
    {
        return $this->belongsToMany(Safe::class, 'safe_user');
    }
}
