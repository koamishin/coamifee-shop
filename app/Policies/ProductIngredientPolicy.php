<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ProductIngredient;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductIngredientPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ProductIngredient');
    }

    public function view(AuthUser $authUser, ProductIngredient $productIngredient): bool
    {
        return $authUser->can('View:ProductIngredient');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ProductIngredient');
    }

    public function update(AuthUser $authUser, ProductIngredient $productIngredient): bool
    {
        return $authUser->can('Update:ProductIngredient');
    }

    public function delete(AuthUser $authUser, ProductIngredient $productIngredient): bool
    {
        return $authUser->can('Delete:ProductIngredient');
    }

    public function restore(AuthUser $authUser, ProductIngredient $productIngredient): bool
    {
        return $authUser->can('Restore:ProductIngredient');
    }

    public function forceDelete(AuthUser $authUser, ProductIngredient $productIngredient): bool
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

    public function replicate(AuthUser $authUser, ProductIngredient $productIngredient): bool
    {
        return $authUser->can('Replicate:ProductIngredient');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ProductIngredient');
    }

}