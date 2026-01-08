<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

final class IngredientInventoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:IngredientInventory');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('View:IngredientInventory');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:IngredientInventory');
    }

    public function update(AuthUser $authUser): bool
    {
        return $authUser->can('Update:IngredientInventory');
    }

    public function delete(AuthUser $authUser): bool
    {
        return $authUser->can('Delete:IngredientInventory');
    }

    public function restore(AuthUser $authUser): bool
    {
        return $authUser->can('Restore:IngredientInventory');
    }

    public function forceDelete(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDelete:IngredientInventory');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:IngredientInventory');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:IngredientInventory');
    }

    public function replicate(AuthUser $authUser): bool
    {
        return $authUser->can('Replicate:IngredientInventory');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:IngredientInventory');
    }
}
