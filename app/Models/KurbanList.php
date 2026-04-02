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

    public function getTitle(): string
    {
        return ($this->season?->year ?? '?').' - '.($this->collector?->name ?? '?');
    }

    public function __toString(): string
    {
        return $this->getTitle();
    }
}
