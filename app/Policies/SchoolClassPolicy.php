<?php

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class SchoolClassPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_school_class');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('view_school_class');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_school_class');
    }

    public function update(AuthUser $authUser): bool
    {
        return $authUser->can('update_school_class');
    }

    public function delete(AuthUser $authUser): bool
    {
        return $authUser->can('delete_school_class');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_school_class');
    }

    public function restore(AuthUser $authUser): bool
    {
        return $authUser->can('restore_school_class');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_school_class');
    }

    public function forceDelete(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_school_class');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_school_class');
    }

    public function replicate(AuthUser $authUser): bool
    {
        return $authUser->can('replicate_school_class');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_school_class');
    }
}
