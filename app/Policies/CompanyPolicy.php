<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Company;
use Illuminate\Auth\Access\HandlesAuthorization;

class CompanyPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_company');
    }

    public function view(AuthUser $authUser, Company $company): bool
    {
        return $authUser->can('view_company');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_company');
    }

    public function update(AuthUser $authUser, Company $company): bool
    {
        return $authUser->can('update_company');
    }

    public function delete(AuthUser $authUser, Company $company): bool
    {
        return $authUser->can('delete_company');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_company');
    }

    public function restore(AuthUser $authUser, Company $company): bool
    {
        return $authUser->can('restore_company');
    }

    public function forceDelete(AuthUser $authUser, Company $company): bool
    {
        return $authUser->can('force_delete_company');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_company');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_company');
    }

    public function replicate(AuthUser $authUser, Company $company): bool
    {
        return $authUser->can('replicate_company');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_company');
    }

}