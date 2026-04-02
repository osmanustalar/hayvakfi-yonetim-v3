<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Scopes\CompanyScope;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class KurbanList extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'company_id',
        'kurban_season_id',
        'collector_user_id',
        'name',
        'description',
        'is_active',
        'created_user_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(KurbanSeason::class, 'kurban_season_id');
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collector_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_user_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(KurbanEntry::class, 'kurban_list_id');
    }

    public function bulkTransactions(): HasMany
    {
        return $this->hasMany(SafeTransaction::class, 'kurban_list_id');
    }

    public function getTotalSharesAttribute(): int
    {
        return $this->entries()->count();
    }

    public function getIndividualPaidSharesAttribute(): int
    {
        return $this->entries()->where('is_paid', true)->count();
    }

    public function getBulkPaidSharesAttribute(): int
    {
        return (int) $this->bulkTransactions()->sum('share_count');
    }

    public function getTotalPaidSharesAttribute(): int
    {
        return $this->individual_paid_shares + $this->bulk_paid_shares;
    }

    public function getRemainingSharesAttribute(): int
    {
        return max(0, $this->total_shares - $this->total_paid_shares);
    }
    
    public function getIsCompletedAttribute(): bool
    {
        return $this->total_paid_shares >= $this->total_shares && $this->total_shares > 0;
    }

    public function getTitle(): string
    {
        return ($this->season?->year ?? '?').' - '.($this->collector?->name ?? '?');
    }

    public function __toString(): string
    {
        return $this->getTitle();
    }
}
