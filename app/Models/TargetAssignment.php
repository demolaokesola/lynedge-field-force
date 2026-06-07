<?php

namespace App\Models;

use App\Enums\AssignmentReason;
use App\Enums\TargetBasis;
use Database\Factories\TargetAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['cycle_id', 'user_id', 'position_id', 'target_tier_id', 'basis', 'effective_from', 'effective_to', 'reason', 'notes'])]
class TargetAssignment extends Model
{
    /** @use HasFactory<TargetAssignmentFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Cycle, $this>
     */
    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Position, $this>
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * @return BelongsTo<TargetTier, $this>
     */
    public function tier(): BelongsTo
    {
        return $this->belongsTo(TargetTier::class, 'target_tier_id');
    }

    /**
     * @return HasMany<TargetAssignmentLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(TargetAssignmentLine::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'basis' => TargetBasis::class,
            'reason' => AssignmentReason::class,
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }
}
