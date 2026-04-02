<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContactType;
use App\Enums\TransactionType;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SafeTransactionCategory extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'parent_id',
        'sort_order',
        'is_active',
        'is_disable_in_report',
        'is_sacrifice_type',
        'contact_type',
        'color',
        'description',
        'created_user_id',
    ];

    protected function casts(): array
    {
        return [
            'type'                 => TransactionType::class,
            'contact_type'        => ContactType::class,
            'is_active'           => 'boolean',
            'is_disable_in_report' => 'boolean',
            'is_sacrifice_type'    => 'boolean',
            'sort_order'          => 'integer',
        ];
    }

    // No CompanyScope — queried with: WHERE company_id IS NULL OR company_id = :active_company_id

    public function parent(): BelongsTo
    {
        return $this->belongsTo(SafeTransactionCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(SafeTransactionCategory::class, 'parent_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_user_id');
    }

    public function scopeForActiveCompany(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->whereNull('company_id')
              ->orWhere('company_id', session('active_company_id'));
        });
    }
}
