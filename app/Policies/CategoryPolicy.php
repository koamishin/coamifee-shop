<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

final class CategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Category');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('View:Category');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Category');
    }

    public function update(AuthUser $authUser): bool
    {
        return $authUser->can('Update:Category');
    }

    public function delete(AuthUser $authUser): bool
    {
        return $authUser->can('Delete:Category');
    }

    public function restore(AuthUser $authUser): bool
    {
        return $authUser->can('Restore:Category');
    }

    public function forceDelete(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDelete:Category');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Category');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Category');
    }

    public function replicate(AuthUser $authUser): bool
    {
        return $authUser->can('Replicate:Category');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Category');
    }
}
