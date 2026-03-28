<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'national_id',
        'birth_date',
        'address',
        'city',
        'is_donor',
        'is_aid_recipient',
        'is_student',
        'notes',
        'created_user_id',
    ];

    protected function casts(): array
    {
        return [
            'is_donor'         => 'boolean',
            'is_aid_recipient' => 'boolean',
            'is_student'       => 'boolean',
            'birth_date'       => 'date',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_user_id');
    }
}
