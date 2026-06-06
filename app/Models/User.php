<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'region_id', 'is_active'])]
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
     * The region this user is anchored to. Set only for region-scoped non-reps
     * (regional_head, optionally accountant); reps derive region from their Position.
     *
     * @return BelongsTo<Region, $this>
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Every occupancy this user has ever held.
     *
     * @return HasMany<PositionAssignment, $this>
     */
    public function positionAssignments(): HasMany
    {
        return $this->hasMany(PositionAssignment::class);
    }

    /**
     * The user's current open occupancy, if any (effective_to IS NULL). A rep/supervisor
     * is anchored to the org tree through this, not through users.region_id.
     *
     * @return HasOne<PositionAssignment, $this>
     */
    public function activePositionAssignment(): HasOne
    {
        return $this->hasOne(PositionAssignment::class)->whereNull('effective_to');
    }

    /**
     * The region this user reads against: the explicit region_id when set (regional_head,
     * optionally accountant), otherwise derived from the open position's territory. Null
     * when neither is present — callers must treat that as "no region", never "all".
     */
    public function currentRegionId(): ?int
    {
        if ($this->region_id !== null) {
            return $this->region_id;
        }

        return $this->activePositionAssignment?->position?->territory?->region_id;
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
            'is_active' => 'boolean',
        ];
    }
}
