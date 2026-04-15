<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\KurbanEntry;
use Illuminate\Auth\Access\HandlesAuthorization;

class KurbanEntryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_kurban_entry');
    }

    public function view(AuthUser $authUser, KurbanEntry $kurbanEntry): bool
    {
        return $authUser->can('view_kurban_entry');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_kurban_entry');
    }

    public function update(AuthUser $authUser, KurbanEntry $kurbanEntry): bool
    {
        return $authUser->can('update_kurban_entry');
    }

    public function delete(AuthUser $authUser, KurbanEntry $kurbanEntry): bool
    {
        return $authUser->can('delete_kurban_entry');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_kurban_entry');
    }

    public function restore(AuthUser $authUser, KurbanEntry $kurbanEntry): bool
    {
        return $authUser->can('restore_kurban_entry');
    }

    public function forceDelete(AuthUser $authUser, KurbanEntry $kurbanEntry): bool
    {
        return $authUser->can('force_delete_kurban_entry');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_kurban_entry');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_kurban_entry');
    }

    public function replicate(AuthUser $authUser, KurbanEntry $kurbanEntry): bool
    {
        return $authUser->can('replicate_kurban_entry');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_kurban_entry');
    }

}