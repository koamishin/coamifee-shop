<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ProductMetric;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductMetricPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ProductMetric');
    }

    public function view(AuthUser $authUser, ProductMetric $productMetric): bool
    {
        return $authUser->can('View:ProductMetric');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ProductMetric');
    }

    public function update(AuthUser $authUser, ProductMetric $productMetric): bool
    {
        return $authUser->can('Update:ProductMetric');
    }

    public function delete(AuthUser $authUser, ProductMetric $productMetric): bool
    {
        return $authUser->can('Delete:ProductMetric');
    }

    public function restore(AuthUser $authUser, ProductMetric $productMetric): bool
    {
        return $authUser->can('Restore:ProductMetric');
    }

    public function forceDelete(AuthUser $authUser, ProductMetric $productMetric): bool
    {
        return $authUser->can('ForceDelete:ProductMetric');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ProductMetric');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ProductMetric');
    }

    public function replicate(AuthUser $authUser, ProductMetric $productMetric): bool
    {
        return $authUser->can('Replicate:ProductMetric');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ProductMetric');
    }

}