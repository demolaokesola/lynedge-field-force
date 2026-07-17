<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

/**
 * Master-data is admin-only for write access. sales_rep/supervisor may additionally
 * view products (read-only "My Products" in the field panel, scoped to their team via
 * RepScope::productsForUser). The superuser role is granted everything automatically
 * via Shield's Gate::before.
 */
class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['platform_admin', 'sales_rep', 'supervisor']);
    }

    public function view(User $user, Product $product): bool
    {
        return $user->hasAnyRole(['platform_admin', 'sales_rep', 'supervisor']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->hasRole('platform_admin');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole('platform_admin');
    }
}
