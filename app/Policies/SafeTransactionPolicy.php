<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SafeTransaction;
use Illuminate\Auth\Access\HandlesAuthorization;

class SafeTransactionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_safe_transaction');
    }

    public function view(AuthUser $authUser, SafeTransaction $safeTransaction): bool
    {
        return $authUser->can('view_safe_transaction');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_safe_transaction');
    }

    public function update(AuthUser $authUser, SafeTransaction $safeTransaction): bool
    {
        return $authUser->can('update_safe_transaction');
    }

    public function delete(AuthUser $authUser, SafeTransaction $safeTransaction): bool
    {
        return $authUser->can('delete_safe_transaction');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_safe_transaction');
    }

    public function restore(AuthUser $authUser, SafeTransaction $safeTransaction): bool
    {
        return $authUser->can('restore_safe_transaction');
    }

    public function forceDelete(AuthUser $authUser, SafeTransaction $safeTransaction): bool
    {
        return $authUser->can('force_delete_safe_transaction');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_safe_transaction');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_safe_transaction');
    }

    public function replicate(AuthUser $authUser, SafeTransaction $safeTransaction): bool
    {
        return $authUser->can('replicate_safe_transaction');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_safe_transaction');
    }

}