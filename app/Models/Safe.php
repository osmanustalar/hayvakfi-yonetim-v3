<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Scopes\CompanyScope;
use App\Models\Scopes\UserSafeScope;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Safe extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'company_id',
        'safe_group_id',
        'name',
        'iban',
        'currency_id',
        'balance',
        'is_active',
        'sort_order',
        'last_processed_at',
        'integration_id',
        'created_user_id',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:4',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'last_processed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
        static::addGlobalScope(new UserSafeScope);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function safeGroup(): BelongsTo
    {
        return $this->belongsTo(SafeGroup::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_user_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(SafeTransaction::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'safe_user');
    }
}
