<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Safe;
use Illuminate\Auth\Access\HandlesAuthorization;

class SafePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_safe');
    }

    public function view(AuthUser $authUser, Safe $safe): bool
    {
        return $authUser->can('view_safe');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_safe');
    }

    public function update(AuthUser $authUser, Safe $safe): bool
    {
        return $authUser->can('update_safe');
    }

    public function delete(AuthUser $authUser, Safe $safe): bool
    {
        return $authUser->can('delete_safe');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_safe');
    }

    public function restore(AuthUser $authUser, Safe $safe): bool
    {
        return $authUser->can('restore_safe');
    }

    public function forceDelete(AuthUser $authUser, Safe $safe): bool
    {
        return $authUser->can('force_delete_safe');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_safe');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_safe');
    }

    public function replicate(AuthUser $authUser, Safe $safe): bool
    {
        return $authUser->can('replicate_safe');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_safe');
    }

}