<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class UserSafeScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = auth()->user();

        if ($user === null) {
            return;
        }

        // Sadece atanan kasaları gör (super_admin da dahil)
        $builder->whereHas('users', fn (Builder $q) => $q->where('users.id', $user->id));
    }
}
