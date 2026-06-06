<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesSeeder extends Seeder
{
    /**
     * Every application role. 'superuser' is Shield's super-admin (all abilities via
     * Gate::before); the rest gate panel entry in {@see User::canAccessPanel()}.
     *
     * @var list<string>
     */
    public const ROLES = [
        'superuser',
        'platform_admin',
        'accountant',
        'hq_lead',
        'regional_head',
        'supervisor',
        'sales_rep',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::ROLES as $role) {
            Role::findOrCreate($role, 'web');
        }
    }
}
