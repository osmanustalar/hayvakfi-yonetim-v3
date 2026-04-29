<?php

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class StudentEnrollmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_student_enrollment');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('view_student_enrollment');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_student_enrollment');
    }

    public function update(AuthUser $authUser): bool
    {
        return $authUser->can('update_student_enrollment');
    }

    public function delete(AuthUser $authUser): bool
    {
        return $authUser->can('delete_student_enrollment');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_student_enrollment');
    }

    public function restore(AuthUser $authUser): bool
    {
        return $authUser->can('restore_student_enrollment');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_student_enrollment');
    }

    public function forceDelete(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_student_enrollment');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_student_enrollment');
    }

    public function replicate(AuthUser $authUser): bool
    {
        return $authUser->can('replicate_student_enrollment');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_student_enrollment');
    }
}
