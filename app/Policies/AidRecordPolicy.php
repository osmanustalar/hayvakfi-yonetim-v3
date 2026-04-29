<?php

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class AidRecordPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_aid_record');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('view_aid_record');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_aid_record');
    }

    public function update(AuthUser $authUser): bool
    {
        return $authUser->can('update_aid_record');
    }

    public function delete(AuthUser $authUser): bool
    {
        return $authUser->can('delete_aid_record');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_aid_record');
    }

    public function restore(AuthUser $authUser): bool
    {
        return $authUser->can('restore_aid_record');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_aid_record');
    }

    public function forceDelete(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_aid_record');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_aid_record');
    }

    public function replicate(AuthUser $authUser): bool
    {
        return $authUser->can('replicate_aid_record');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_aid_record');
    }
}
