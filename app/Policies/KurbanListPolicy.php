<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\KurbanList;
use Illuminate\Auth\Access\HandlesAuthorization;

class KurbanListPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_kurban_list');
    }

    public function view(AuthUser $authUser, KurbanList $kurbanList): bool
    {
        return $authUser->can('view_kurban_list');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_kurban_list');
    }

    public function update(AuthUser $authUser, KurbanList $kurbanList): bool
    {
        return $authUser->can('update_kurban_list');
    }

    public function delete(AuthUser $authUser, KurbanList $kurbanList): bool
    {
        return $authUser->can('delete_kurban_list');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_kurban_list');
    }

    public function restore(AuthUser $authUser, KurbanList $kurbanList): bool
    {
        return $authUser->can('restore_kurban_list');
    }

    public function forceDelete(AuthUser $authUser, KurbanList $kurbanList): bool
    {
        return $authUser->can('force_delete_kurban_list');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_kurban_list');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_kurban_list');
    }

    public function replicate(AuthUser $authUser, KurbanList $kurbanList): bool
    {
        return $authUser->can('replicate_kurban_list');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_kurban_list');
    }

}