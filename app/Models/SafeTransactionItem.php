<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Scopes\CompanyScope;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SafeTransactionItem extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'company_id',
        'transaction_id',
        'transaction_category_id',
        'donation_category_id',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope());
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(SafeTransaction::class, 'transaction_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(SafeTransactionCategory::class, 'transaction_category_id');
    }

    public function donationCategory(): BelongsTo
    {
        return $this->belongsTo(SafeTransactionCategory::class, 'donation_category_id');
    }
}
