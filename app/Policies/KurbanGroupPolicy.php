<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\KurbanGroup;
use Illuminate\Auth\Access\HandlesAuthorization;

class KurbanGroupPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_kurban_group');
    }

    public function view(AuthUser $authUser, KurbanGroup $kurbanGroup): bool
    {
        return $authUser->can('view_kurban_group');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_kurban_group');
    }

    public function update(AuthUser $authUser, KurbanGroup $kurbanGroup): bool
    {
        return $authUser->can('update_kurban_group');
    }

    public function delete(AuthUser $authUser, KurbanGroup $kurbanGroup): bool
    {
        return $authUser->can('delete_kurban_group');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_kurban_group');
    }

    public function restore(AuthUser $authUser, KurbanGroup $kurbanGroup): bool
    {
        return $authUser->can('restore_kurban_group');
    }

    public function forceDelete(AuthUser $authUser, KurbanGroup $kurbanGroup): bool
    {
        return $authUser->can('force_delete_kurban_group');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_kurban_group');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_kurban_group');
    }

    public function replicate(AuthUser $authUser, KurbanGroup $kurbanGroup): bool
    {
        return $authUser->can('replicate_kurban_group');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_kurban_group');
    }

}