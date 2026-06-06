<?php

namespace App\Models;

use App\Enums\CallType;
use App\Models\Concerns\ScopesToViewer;
use Database\Factories\CallFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A rep's detailing activity: a physical visit or phone call to a demand creator, with
 * the products detailed on it. The first transaction model carrying both user_id and
 * territory_id, so {@see ScopesToViewer} keys its visibility off those columns.
 *
 * territory_id is denormalised from the position at write time (reorg history) and, with
 * user_id, is set server-side — neither is mass-assignable from the client.
 */
#[Fillable(['position_id', 'territory_id', 'demand_creator_id', 'call_type', 'called_at', 'latitude', 'longitude', 'notes'])]
class Call extends Model
{
    /** @use HasFactory<CallFactory> */
    use HasFactory, ScopesToViewer;

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
     * @return BelongsTo<Territory, $this>
     */
    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class);
    }

    /**
     * @return BelongsTo<DemandCreator, $this>
     */
    public function demandCreator(): BelongsTo
    {
        return $this->belongsTo(DemandCreator::class);
    }

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'call_type' => CallType::class,
            'called_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }
}
