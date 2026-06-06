<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Which roles may enter each panel. A panel is navigation/UX only — this gate
     * controls entry, never row/action access (that lives in Policies + scopeVisibleTo).
     * The 'superuser' role bypasses this via the early return below.
     *
     * @var array<string, list<string>>
     */
    private const PANEL_ROLES = [
        'field' => ['sales_rep', 'supervisor'],
        'office' => ['platform_admin', 'accountant'],
        'management' => ['hq_lead', 'regional_head'],
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->hasRole('superuser')) {
            return true;
        }

        return $this->hasAnyRole(self::PANEL_ROLES[$panel->getId()] ?? []);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
