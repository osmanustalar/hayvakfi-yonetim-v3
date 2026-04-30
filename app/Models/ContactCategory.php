<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContactType;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactCategory extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'name',
        'color',
        'description',
        'is_active',
        'contact_type',
        'created_user_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active'    => 'boolean',
            'contact_type' => ContactType::class,
        ];
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'contact_contact_category')
            ->withTimestamps();
    }

    public function scopeForType(Builder $query, ContactType $type): Builder
    {
        return $query->where('contact_type', $type);
    }

    public static function findByType(ContactType $type): ?self
    {
        return static::where('contact_type', $type)->first();
    }
}
