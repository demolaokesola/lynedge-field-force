<?php

namespace App\Providers;

use App\Models\Position;
use App\Models\TargetAssignment;
use App\Models\Territory;
use App\Observers\PositionObserver;
use App\Observers\TargetAssignmentObserver;
use App\Observers\TerritoryObserver;
use Illuminate\Support\ServiceProvider;

/**
 * Wires domain-model observers. The bindings are guarded by class_exists() so this
 * provider is a no-op today (no domain models yet) and activates automatically once
 * each model is introduced in a later phase — no edit to this provider required then.
 *
 * @var array<class-string, class-string>
 */
class ObserverServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    private const OBSERVERS = [
        Position::class => PositionObserver::class,
        Territory::class => TerritoryObserver::class,
        TargetAssignment::class => TargetAssignmentObserver::class,
    ];

    public function boot(): void
    {
        foreach (self::OBSERVERS as $model => $observer) {
            if (class_exists($model)) {
                $model::observe($observer);
            }
        }
    }
}
