<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LivestockType;
use App\Models\Scopes\CompanyScope;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class KurbanEntry extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'company_id',
        'kurban_list_id',
        'queue_number',
        'contact_id',
        'sacrifice_category_id',
        'livestock_type',
        'notes',
        'is_paid',
        'paid_date',
        'safe_transaction_id',
        'created_user_id',
    ];

    protected function casts(): array
    {
        return [
            'is_paid' => 'boolean',
            'paid_date' => 'date',
            'livestock_type' => LivestockType::class,
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

    public function list(): BelongsTo
    {
        return $this->belongsTo(KurbanList::class, 'kurban_list_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function sacrificeCategory(): BelongsTo
    {
        return $this->belongsTo(SafeTransactionCategory::class, 'sacrifice_category_id');
    }

    public function safeTransaction(): BelongsTo
    {
        return $this->belongsTo(SafeTransaction::class, 'safe_transaction_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_user_id');
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->contact?->first_name.' '.$this->contact?->last_name,
        );
    }
}
