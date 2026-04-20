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

class StudentEnrollment extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'company_id',
        'class_id',
        'contact_id',
        'enrollment_date',
        'monthly_fee',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'enrollment_date' => 'date',
            'monthly_fee' => 'decimal:2',
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

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function fees(): HasMany
    {
        return $this->hasMany(StudentFee::class, 'enrollment_id');
    }
}
