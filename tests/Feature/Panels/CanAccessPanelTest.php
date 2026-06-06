<?php

use App\Models\User;
use Filament\Facades\Filament;

/**
 * @return list<string>
 */
function panelIds(): array
{
    return ['field', 'office', 'management'];
}

dataset('role panel access', [
    // role => panel it may enter
    'sales_rep' => ['sales_rep', 'field'],
    'supervisor' => ['supervisor', 'field'],
    'platform_admin' => ['platform_admin', 'office'],
    'accountant' => ['accountant', 'office'],
    'hq_lead' => ['hq_lead', 'management'],
    'regional_head' => ['regional_head', 'management'],
]);

test('each role may enter only its own panel', function (string $role, string $allowedPanel): void {
    $user = User::factory()->withRole($role)->create();

    foreach (panelIds() as $panelId) {
        expect($user->canAccessPanel(Filament::getPanel($panelId)))
            ->toBe($panelId === $allowedPanel, "role [{$role}] vs panel [{$panelId}]");
    }
})->with('role panel access');

test('superuser may enter every panel', function (): void {
    $user = User::factory()->withRole('superuser')->create();

    foreach (panelIds() as $panelId) {
        expect($user->canAccessPanel(Filament::getPanel($panelId)))->toBeTrue();
    }
});

test('a user with no role is locked out of every panel', function (): void {
    $user = User::factory()->create();

    foreach (panelIds() as $panelId) {
        expect($user->canAccessPanel(Filament::getPanel($panelId)))->toBeFalse();
    }
});
