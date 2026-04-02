<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LivestockType;
use App\Models\Scopes\CompanyScope;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class KurbanSeason extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'company_id',
        'year',
        'price_try',
        'price_eur',
        'default_livestock_type',
        'is_active',
        'description',
        'created_user_id',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'price_try' => 'decimal:2',
            'price_eur' => 'decimal:2',
            'default_livestock_type' => LivestockType::class,
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_user_id');
    }

    public function lists(): HasMany
    {
        return $this->hasMany(KurbanList::class, 'kurban_season_id');
    }

    public function groups(): HasMany
    {
        return $this->hasMany(KurbanGroup::class, 'kurban_season_id');
    }

    public function entries(): HasManyThrough
    {
        return $this->hasManyThrough(KurbanEntry::class, KurbanList::class, 'kurban_season_id', 'kurban_list_id');
    }
}
