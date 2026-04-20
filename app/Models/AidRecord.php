<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Scopes\CompanyScope;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AidRecord extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'company_id',
        'contact_id',
        'transaction_id',
        'aid_type',
        'description',
        'amount',
        'given_at',
        'created_user_id',
    ];

    protected function casts(): array
    {
        return [
            'given_at' => 'date',
            'amount' => 'decimal:2',
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

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(SafeTransaction::class, 'transaction_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_user_id');
    }
}
