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

class SchoolClass extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'teacher_id',
        'start_date',
        'end_date',
        'default_monthly_fee',
        'capacity',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
            'default_monthly_fee' => 'decimal:2',
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

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class, 'class_id');
    }
}
