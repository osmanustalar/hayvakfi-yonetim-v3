<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\KurbanSeason;
use Illuminate\Auth\Access\HandlesAuthorization;

class KurbanSeasonPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_kurban_season');
    }

    public function view(AuthUser $authUser, KurbanSeason $kurbanSeason): bool
    {
        return $authUser->can('view_kurban_season');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_kurban_season');
    }

    public function update(AuthUser $authUser, KurbanSeason $kurbanSeason): bool
    {
        return $authUser->can('update_kurban_season');
    }

    public function delete(AuthUser $authUser, KurbanSeason $kurbanSeason): bool
    {
        return $authUser->can('delete_kurban_season');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_kurban_season');
    }

    public function restore(AuthUser $authUser, KurbanSeason $kurbanSeason): bool
    {
        return $authUser->can('restore_kurban_season');
    }

    public function forceDelete(AuthUser $authUser, KurbanSeason $kurbanSeason): bool
    {
        return $authUser->can('force_delete_kurban_season');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_kurban_season');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_kurban_season');
    }

    public function replicate(AuthUser $authUser, KurbanSeason $kurbanSeason): bool
    {
        return $authUser->can('replicate_kurban_season');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_kurban_season');
    }

}