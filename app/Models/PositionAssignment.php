<?php

namespace App\Models;

use App\Enums\AssignmentStatus;
use Database\Factories\PositionAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Temporal occupancy of a Position by a user. The open assignment (effective_to
 * IS NULL) is the current occupant; setting effective_to ends it.
 */
#[Fillable(['position_id', 'user_id', 'effective_from', 'effective_to', 'status', 'notes'])]
class PositionAssignment extends Model
{
    /** @use HasFactory<PositionAssignmentFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Position, $this>
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_to' => 'date',
            'status' => AssignmentStatus::class,
        ];
    }
}
