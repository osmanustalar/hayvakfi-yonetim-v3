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

class KurbanGroup extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const MAX_MEMBERS = 7;

    protected $fillable = [
        'company_id',
        'kurban_season_id',
        'group_no',
        'code',
        'logo1',
        'logo2',
        'notes',
        'created_user_id',
    ];

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

    public function entries(): HasMany
    {
        return $this->hasMany(KurbanEntry::class, 'kurban_group_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_user_id');
    }

    public function getMemberCountAttribute(): int
    {
        return $this->entries()->count();
    }

    public function hasCapacity(): bool
    {
        return $this->member_count < self::MAX_MEMBERS;
    }

    public function isFull(): bool
    {
        return $this->member_count >= self::MAX_MEMBERS;
    }
}
