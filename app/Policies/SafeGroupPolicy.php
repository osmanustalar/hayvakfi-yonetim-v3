<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SafeGroup;
use Illuminate\Auth\Access\HandlesAuthorization;

class SafeGroupPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_safe_group');
    }

    public function view(AuthUser $authUser, SafeGroup $safeGroup): bool
    {
        return $authUser->can('view_safe_group');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_safe_group');
    }

    public function update(AuthUser $authUser, SafeGroup $safeGroup): bool
    {
        return $authUser->can('update_safe_group');
    }

    public function delete(AuthUser $authUser, SafeGroup $safeGroup): bool
    {
        return $authUser->can('delete_safe_group');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_safe_group');
    }

    public function restore(AuthUser $authUser, SafeGroup $safeGroup): bool
    {
        return $authUser->can('restore_safe_group');
    }

    public function forceDelete(AuthUser $authUser, SafeGroup $safeGroup): bool
    {
        return $authUser->can('force_delete_safe_group');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_safe_group');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_safe_group');
    }

    public function replicate(AuthUser $authUser, SafeGroup $safeGroup): bool
    {
        return $authUser->can('replicate_safe_group');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_safe_group');
    }

}