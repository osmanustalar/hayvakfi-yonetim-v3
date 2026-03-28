<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SafeTransactionCategory;
use Illuminate\Auth\Access\HandlesAuthorization;

class SafeTransactionCategoryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_safe_transaction_category');
    }

    public function view(AuthUser $authUser, SafeTransactionCategory $safeTransactionCategory): bool
    {
        return $authUser->can('view_safe_transaction_category');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_safe_transaction_category');
    }

    public function update(AuthUser $authUser, SafeTransactionCategory $safeTransactionCategory): bool
    {
        return $authUser->can('update_safe_transaction_category');
    }

    public function delete(AuthUser $authUser, SafeTransactionCategory $safeTransactionCategory): bool
    {
        return $authUser->can('delete_safe_transaction_category');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_safe_transaction_category');
    }

    public function restore(AuthUser $authUser, SafeTransactionCategory $safeTransactionCategory): bool
    {
        return $authUser->can('restore_safe_transaction_category');
    }

    public function forceDelete(AuthUser $authUser, SafeTransactionCategory $safeTransactionCategory): bool
    {
        return $authUser->can('force_delete_safe_transaction_category');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_safe_transaction_category');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_safe_transaction_category');
    }

    public function replicate(AuthUser $authUser, SafeTransactionCategory $safeTransactionCategory): bool
    {
        return $authUser->can('replicate_safe_transaction_category');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_safe_transaction_category');
    }

}