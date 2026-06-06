<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * One demo user per role so every panel is immediately loginable. All use the
 * password "password". Idempotent via updateOrCreate on the email.
 */
class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        foreach (RolesSeeder::ROLES as $role) {
            $user = User::updateOrCreate(
                ['email' => "{$role}@lynedge.test"],
                [
                    'name' => str($role)->headline()->toString(),
                    'password' => 'password',
                    'email_verified_at' => now(),
                ],
            );

            $user->syncRoles([$role]);
        }
    }
}
