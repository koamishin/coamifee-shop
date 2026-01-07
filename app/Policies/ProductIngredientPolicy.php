<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

final class ProductIngredientPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ProductIngredient');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('View:ProductIngredient');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ProductIngredient');
    }

    public function update(AuthUser $authUser): bool
    {
        return $authUser->can('Update:ProductIngredient');
    }

    public function delete(AuthUser $authUser): bool
    {
        return $authUser->can('Delete:ProductIngredient');
    }

    public function restore(AuthUser $authUser): bool
    {
        return $authUser->can('Restore:ProductIngredient');
    }

    public function forceDelete(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDelete:ProductIngredient');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ProductIngredient');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ProductIngredient');
    }

    public function replicate(AuthUser $authUser): bool
    {
        return $authUser->can('Replicate:ProductIngredient');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ProductIngredient');
    }
}
