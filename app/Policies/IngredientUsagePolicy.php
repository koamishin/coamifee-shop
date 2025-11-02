<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\IngredientUsage;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

final class IngredientUsagePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:IngredientUsage');
    }

    public function view(AuthUser $authUser, IngredientUsage $ingredientUsage): bool
    {
        return $authUser->can('View:IngredientUsage');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:IngredientUsage');
    }

    public function update(AuthUser $authUser, IngredientUsage $ingredientUsage): bool
    {
        return $authUser->can('Update:IngredientUsage');
    }

    public function delete(AuthUser $authUser, IngredientUsage $ingredientUsage): bool
    {
        return $authUser->can('Delete:IngredientUsage');
    }

    public function restore(AuthUser $authUser, IngredientUsage $ingredientUsage): bool
    {
        return $authUser->can('Restore:IngredientUsage');
    }

    public function forceDelete(AuthUser $authUser, IngredientUsage $ingredientUsage): bool
    {
        return $authUser->can('ForceDelete:IngredientUsage');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:IngredientUsage');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:IngredientUsage');
    }

    public function replicate(AuthUser $authUser, IngredientUsage $ingredientUsage): bool
    {
        return $authUser->can('Replicate:IngredientUsage');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:IngredientUsage');
    }
}
