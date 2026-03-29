<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OperationType;
use App\Enums\TransactionType;
use App\Models\Scopes\CompanyScope;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SafeTransaction extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'company_id',
        'safe_id',
        'type',
        'operation_type',
        'total_amount',
        'currency_id',
        'exchange_rate',
        'item_rate',
        'target_safe_id',
        'target_transaction_id',
        'contact_id',
        'reference_user_id',
        'created_user_id',
        'process_date',
        'transaction_date',
        'balance_after_created',
        'integration_id',
        'import_file',
        'is_show',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'type'                  => TransactionType::class,
            'operation_type'        => OperationType::class,
            'total_amount'          => 'decimal:4',
            'exchange_rate'         => 'decimal:4',
            'item_rate'             => 'decimal:4',
            'balance_after_created' => 'decimal:4',
            'process_date'          => 'date',
            'transaction_date'      => 'datetime',
            'is_show'               => 'boolean',
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

    public function safe(): BelongsTo
    {
        return $this->belongsTo(Safe::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function targetSafe(): BelongsTo
    {
        return $this->belongsTo(Safe::class, 'target_safe_id');
    }

    public function targetTransaction(): BelongsTo
    {
        return $this->belongsTo(SafeTransaction::class, 'target_transaction_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function referenceUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reference_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SafeTransactionItem::class, 'transaction_id');
    }
}
