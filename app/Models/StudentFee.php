<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FeeStatus;
use App\Models\Scopes\CompanyScope;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentFee extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'company_id',
        'enrollment_id',
        'period',
        'amount',
        'due_date',
        'paid_at',
        'payment_transaction_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'period' => 'date',
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'amount' => 'decimal:2',
            'status' => FeeStatus::class,
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

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class);
    }

    public function paymentTransaction(): BelongsTo
    {
        return $this->belongsTo(SafeTransaction::class, 'payment_transaction_id');
    }
}
