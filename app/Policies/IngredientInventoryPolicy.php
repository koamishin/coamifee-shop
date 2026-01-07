<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\IngredientInventory;
use Illuminate\Auth\Access\HandlesAuthorization;

class IngredientInventoryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:IngredientInventory');
    }

    public function view(AuthUser $authUser, IngredientInventory $ingredientInventory): bool
    {
        return $authUser->can('View:IngredientInventory');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:IngredientInventory');
    }

    public function update(AuthUser $authUser, IngredientInventory $ingredientInventory): bool
    {
        return $authUser->can('Update:IngredientInventory');
    }

    public function delete(AuthUser $authUser, IngredientInventory $ingredientInventory): bool
    {
        return $authUser->can('Delete:IngredientInventory');
    }

    public function restore(AuthUser $authUser, IngredientInventory $ingredientInventory): bool
    {
        return $authUser->can('Restore:IngredientInventory');
    }

    public function forceDelete(AuthUser $authUser, IngredientInventory $ingredientInventory): bool
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

    public function replicate(AuthUser $authUser, IngredientInventory $ingredientInventory): bool
    {
        return $authUser->can('Replicate:IngredientInventory');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:IngredientInventory');
    }

}